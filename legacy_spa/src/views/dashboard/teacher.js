// src/views/dashboard/teacher.js
import { appState } from '../../state.js';

export function renderTeacherView(teacherId, data) {
    const teacher = appState.currentUser;
    if (!teacher) return '';

    const myOpenCourses = data.courses || [];

    const cards = myOpenCourses.map((course) => ({
        id: `teacher-course-${course.id}`,
        html: `
            <div class="card" id="teacher-course-${course.id}" draggable="true" ondragstart="window.AppHandlers.handleDragStart(event)" ondragend="window.AppHandlers.handleDragEnd(event)">
                <h3 class="card-title">Turma: ${course.name}</h3>
                 <div class="list-item-actions">
                     <button class="action-button secondary" onclick="window.AppHandlers.handleNavigateToCourseDetails(${course.id})">Detalhes</button>
                     <button class="action-button" onclick="window.AppHandlers.handleNavigateToAttendance(${course.id})">Lançar Frequência</button>
                 </div>
            </div>
        `
    }));

    return `
        <div class="welcome-message">
            <h2>Área do Professor - ${teacher.firstName} ${teacher.lastName || ''}</h2>
            <p>Selecione uma turma para gerenciar a frequência.</p>
        </div>
        <div class="dashboard-grid" ondragover="window.AppHandlers.handleDragOver(event)" ondrop="window.AppHandlers.handleDrop(event)">
            ${cards.length === 0 ? '<div class="card"><p>Nenhum curso aberto atribuído a você.</p></div>' : cards.map((c) => c.html).join('')}
        </div>
    `;
}