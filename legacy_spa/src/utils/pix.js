function formatField(id, value) {
    const length = value.length.toString().padStart(2, '0');
    return `${id}${length}${value}`;
}

function crc16(data) {
    let crc = 0xFFFF;
    for (let i = 0; i < data.length; i++) {
        crc ^= data.charCodeAt(i) << 8;
        for (let j = 0; j < 8; j++) {
            if ((crc & 0x8000) !== 0) {
                crc = (crc << 1) ^ 0x1021;
            } else {
                crc <<= 1;
            }
        }
    }
    return ('0000' + (crc & 0xFFFF).toString(16).toUpperCase()).slice(-4);
}

export function generateBrCode(pixKey, amount, merchantName, merchantCity, txid = '***', description = null) {
    const sanitizedName = merchantName.normalize("NFD").replace(/[\u0300-\u036f]/g, "").substring(0, 25);
    const sanitizedCity = merchantCity.normalize("NFD").replace(/[\u0300-\u036f]/g, "").substring(0, 15);
    const amountString = amount ? amount.toFixed(2) : null;
    let merchantAccountInfo = formatField('00', 'br.gov.bcb.pix') + formatField('01', pixKey);
    if (description) {
        const sanitizedDescription = description.normalize("NFD").replace(/[\u0300-\u036f]/g, "").substring(0, 72);
        merchantAccountInfo += formatField('02', sanitizedDescription);
    }
    const payloadFields = [
        formatField('00', '01'),
        formatField('26', merchantAccountInfo),
        formatField('52', '0000'),
        formatField('53', '986'),
    ];
    if (amountString) {
        payloadFields.push(formatField('54', amountString));
    }
    payloadFields.push(
        formatField('58', 'BR'),
        formatField('59', sanitizedName),
        formatField('60', sanitizedCity),
        formatField('62', formatField('05', txid))
    );
    const payload = payloadFields.join('');
    const payloadWithCrcPlaceholder = `${payload}6304`;
    const crc = crc16(payloadWithCrcPlaceholder);
    return `${payloadWithCrcPlaceholder}${crc}`;
}
