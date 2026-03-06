export function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result);
        reader.onerror = error => reject(error);
    });
}

// --- Lógica de Arrastar e Soltar (Drag and Drop) ---

let draggedElement = null;

function getDragAfterElement(container, y) {
    const draggableElements = [...container.querySelectorAll('.card:not(.dragging)')];
    
    return draggableElements.reduce((closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
            return { offset: offset, element: child };
        } else {
            return closest;
        }
    }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
}

export function handleDragStart(event) {
    if (event.target && event.target.classList.contains('card')) {
        draggedElement = event.target;
        setTimeout(() => {
            draggedElement.classList.add('dragging');
        }, 0);
    }
};

export function handleDragEnd() {
    if (draggedElement) {
        draggedElement.classList.remove('dragging');
        draggedElement = null;
    }
};

export function handleDragOver(event) {
    event.preventDefault();
    const container = event.target.closest('.dashboard-grid');
    if (container && draggedElement) {
        const afterElement = getDragAfterElement(container, event.clientY);
        if (afterElement == null) {
            container.appendChild(draggedElement);
        } else {
            container.insertBefore(draggedElement, afterElement);
        }
    }
};

export function handleDrop(event) {
    event.preventDefault();
     const container = event.target.closest('.dashboard-grid');
    if (container && window.appState && window.appState.currentUser) {
        const cardIds = [...container.querySelectorAll('.card')].map(card => card.id);
        localStorage.setItem(`cardOrder_${window.appState.currentUser.id}`, JSON.stringify(cardIds));
    }
};

export function applyCardOrder(appRoot, appState) {
    if (!appState.currentUser) return;
    const grid = appRoot.querySelector('.dashboard-grid');
    if (!grid) return;

    const savedOrder = localStorage.getItem(`cardOrder_${appState.currentUser.id}`);
    if (savedOrder) {
        try {
            const order = JSON.parse(savedOrder);
            const fragment = document.createDocumentFragment();
            order.forEach((cardId) => {
                const card = document.getElementById(cardId);
                if (card) {
                    fragment.appendChild(card);
                }
            });
            grid.appendChild(fragment); // Re-append all cards in the saved order
        } catch (e) {
            console.error("Failed to parse card order from localStorage", e);
        }
    }
}

// *** FUNÇÃO ADICIONADA E EXPORTADA ***
export function getMonthName(monthString) { // monthString no formato 'YYYY-MM'
    if (!monthString) return '';
    try {
        const [year, month] = monthString.split('-');
        // Corrige o bug de off-by-one do mês e garante UTC
        const date = new Date(Date.UTC(year, month - 1, 1)); 
        if (isNaN(date.getTime())) return 'Mês inválido';
        return date.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric', timeZone: 'UTC' });
    } catch (e) {
        console.error("Erro ao formatar nome do mês:", e);
        return 'Erro de mês';
    }
}