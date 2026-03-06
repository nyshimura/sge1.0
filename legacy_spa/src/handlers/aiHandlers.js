// src/handlers/aiHandlers.js
import { apiCall } from '../api.js';

export async function handleGenerateDescription(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    const nameInput = form.elements.namedItem('courseName');
    const descriptionTextarea = form.elements.namedItem('courseDescription');
    const generateButton = form.querySelector('.generate-ai-button'); // Busca pelo seletor de classe

    if (!nameInput || !descriptionTextarea || !generateButton) {
        console.error("Elementos do formulário não encontrados para IA.");
        return;
    }

    const courseName = nameInput.value.trim();
    if (!courseName) {
        return alert('Por favor, insira um nome para o curso antes de gerar a descrição.');
    }

    generateButton.disabled = true;
    generateButton.textContent = 'Gerando...';

    try {
        const data = await apiCall('generateAiDescription', { courseName });
        if (data && data.description) {
            descriptionTextarea.value = data.description; // Preenche a textarea
        } else {
            throw new Error('A resposta da API não continha uma descrição.');
        }
    } catch (error) {
        console.error("AI description generation failed:", error);
        alert(error.message || 'Falha ao gerar descrição com IA.'); // Mostra erro
    } finally {
        generateButton.disabled = false;
        generateButton.textContent = 'Gerar com IA ✨';
    }
}