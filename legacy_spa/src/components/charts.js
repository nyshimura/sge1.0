export function generateRevenueEvolutionChartSVG(data) {
    if (data.length === 0) return '<p>Dados insuficientes para gerar o gráfico.</p>';
    
    const svgWidth = 500;
    const svgHeight = 300;
    const margin = { top: 20, right: 20, bottom: 40, left: 60 };
    const chartWidth = svgWidth - margin.left - margin.right;
    const chartHeight = svgHeight - margin.top - margin.bottom;

    const maxVal = Math.max(...data.flatMap(d => [Number(d.expected), Number(d.collected)]), 0);
    const yScale = (val) => chartHeight - (val / (maxVal > 0 ? maxVal : 1)) * chartHeight;

    const yAxisTicks = 5;
    const yAxisHtml = Array.from({ length: yAxisTicks + 1 }).map((_, i) => {
        const val = (maxVal / yAxisTicks) * i;
        const y = yScale(val);
        return `<g class="y-tick">
            <line x1="${-5}" y1="${y}" x2="${chartWidth}" y2="${y}" stroke="#e0e0e0" stroke-dasharray="2,2" />
            <text x="${-10}" y="${y + 5}" text-anchor="end">R$${val.toFixed(0)}</text>
        </g>`;
    }).join('');

    const barWidth = chartWidth / data.length / 2.5;
    const barGap = chartWidth / data.length;

    const barsHtml = data.map((d, i) => {
        const xExpected = i * barGap + barGap / 2 - barWidth;
        const xCollected = i * barGap + barGap / 2;
        return `
            <g class="bar-group">
                <rect class="bar expected" x="${xExpected}" y="${yScale(Number(d.expected))}" width="${barWidth}" height="${chartHeight - yScale(Number(d.expected))}">
                    <title>Previsto: R$ ${Number(d.expected).toFixed(2)}</title>
                </rect>
                <rect class="bar collected" x="${xCollected}" y="${yScale(Number(d.collected))}" width="${barWidth}" height="${chartHeight - yScale(Number(d.collected))}">
                     <title>Arrecadado: R$ ${Number(d.collected).toFixed(2)}</title>
                </rect>
                <text class="x-axis-label" x="${i * barGap + barGap / 2}" y="${chartHeight + 20}" text-anchor="middle">${d.month}</text>
            </g>`;
    }).join('');

    return `
        <svg viewBox="0 0 ${svgWidth} ${svgHeight}" class="chart bar-chart" preserveAspectRatio="xMidYMid meet">
            <g transform="translate(${margin.left}, ${margin.top})">
                <g class="y-axis">${yAxisHtml}</g>
                <g class="x-axis">
                    <line x1="0" y1="${chartHeight}" x2="${chartWidth}" y2="${chartHeight}" stroke="#333" />
                </g>
                ${barsHtml}
            </g>
             <g class="legend" transform="translate(${margin.left}, ${svgHeight - 10})">
                <rect x="0" y="-10" width="12" height="12" class="bar expected" />
                <text x="18" y="0">Previsto</text>
                <rect x="80" y="-10" width="12" height="12" class="bar collected" />
                <text x="98" y="0">Arrecadado</text>
            </g>
        </svg>
    `;
}

export function generateRevenueByCourseChartSVG(data) {
    const entries = Object.entries(data);
    if (entries.length === 0) return '<p>Nenhuma receita registrada este mês para exibir o gráfico.</p>';
    
    const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6610f2'];
    const total = entries.reduce((sum, [, val]) => sum + Number(val), 0);
    if(total === 0) return '<p>Nenhuma receita registrada este mês para exibir o gráfico.</p>';

    let startAngle = 0;
    const slices = entries.map(([name, value], i) => {
        const percentage = (Number(value) / total);
        const angle = percentage * 360;
        const endAngle = startAngle + angle;
        
        const x1 = Math.cos(Math.PI / 180 * startAngle);
        const y1 = Math.sin(Math.PI / 180 * startAngle);
        const x2 = Math.cos(Math.PI / 180 * endAngle);
        const y2 = Math.sin(Math.PI / 180 * endAngle);
        const largeArcFlag = angle > 180 ? 1 : 0;
        
        const pathData = `M ${x1} ${y1} A 1 1 0 ${largeArcFlag} 1 ${x2} ${y2} L 0 0 Z`;
        
        startAngle = endAngle;

        return `
            <path d="${pathData}" fill="${colors[i % colors.length]}" class="pie-slice">
                <title>${name}: R$ ${Number(value).toFixed(2)} (${(percentage * 100).toFixed(1)}%)</title>
            </path>
        `;
    }).join('');

    const legendHtml = entries.map(([name, value], i) => {
        const percentage = (Number(value) / total * 100).toFixed(1);
        return `
            <div class="legend-item">
                <span class="legend-color" style="background-color: ${colors[i % colors.length]}"></span>
                <span class="legend-label">${name} (${percentage}%)</span>
            </div>
        `;
    }).join('');

    return `
        <div class="pie-chart-container">
            <svg viewBox="-1.1 -1.1 2.2 2.2" class="chart pie-chart">
                ${slices}
            </svg>
            <div class="pie-chart-legend">
                ${legendHtml}
            </div>
        </div>
    `;
}

export function generatePaidVsOutstandingBarChartSVG(data) {
    const svgWidth = 400;
    const svgHeight = 300;
    const margin = { top: 20, right: 20, bottom: 40, left: 60 };
    const chartWidth = svgWidth - margin.left - margin.right;
    const chartHeight = svgHeight - margin.top - margin.bottom;

    const dataEntries = Object.entries(data);
    const maxVal = Math.max(...Object.values(data).map(Number), 0);
    const yScale = (val) => chartHeight - (val / (maxVal > 0 ? maxVal : 1)) * chartHeight;
    
    // <<< CORREÇÃO AQUI: Cores fixas para o gráfico >>>
    const colors = { 
        'Pago': '#28a745',       // Verde para "Pago"
        'Em Aberto': '#fca5a5'   // Vermelho claro para "Em Aberto"
    };

    const yAxisTicks = 5;
    const yAxisHtml = Array.from({ length: yAxisTicks + 1 }).map((_, i) => {
        const val = (maxVal / yAxisTicks) * i;
        const y = yScale(val);
        return `<g class="y-tick">
            <line x1="${-5}" y1="${y}" x2="${chartWidth}" y2="${y}" stroke="#e0e0e0" stroke-dasharray="2,2" />
            <text x="${-10}" y="${y + 5}" text-anchor="end">R$${val.toFixed(0)}</text>
        </g>`;
    }).join('');

    const barWidth = chartWidth / dataEntries.length / 2;
    const barGap = chartWidth / dataEntries.length;

    const barsHtml = dataEntries.map(([status, value], i) => {
        const x = i * barGap + barGap / 2 - barWidth / 2;
        const color = colors[status];
        return `
            <g class="bar-group">
                <rect class="bar" x="${x}" y="${yScale(Number(value))}" width="${barWidth}" height="${chartHeight - yScale(Number(value))}" style="fill: ${color};">
                    <title>${status}: R$ ${Number(value).toFixed(2)}</title>
                </rect>
                 <text class="x-axis-label" x="${i * barGap + barGap / 2}" y="${chartHeight + 20}" text-anchor="middle">${status}</text>
            </g>`;
    }).join('');

    return `
        <svg viewBox="0 0 ${svgWidth} ${svgHeight}" class="chart bar-chart" preserveAspectRatio="xMidYMid meet">
            <g transform="translate(${margin.left}, ${margin.top})">
                <g class="y-axis">${yAxisHtml}</g>
                <g class="x-axis">
                    <line x1="0" y1="${chartHeight}" x2="${chartWidth}" y2="${chartHeight}" stroke="#333" />
                </g>
                ${barsHtml}
            </g>
        </svg>
    `;
}