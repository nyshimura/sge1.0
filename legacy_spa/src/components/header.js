// src/components/header.js
import { appState } from '../state.js';

export function renderHeader(headerElement, appState) {
    if (!headerElement || !appState.schoolProfile) return;

    const logoHtml = appState.schoolProfile.profilePicture
        ? `<img src="${appState.schoolProfile.profilePicture}" alt="Logo da Escola" class="header-logo">`
        : `<span class="logo-icon">🏫</span>`; // Ícone fallback

    let headerContent = `
        <div class="header-content">
             <h1 style="cursor: pointer;" onclick="window.AppHandlers.navigateTo('dashboard')">${logoHtml} ${appState.schoolProfile.name}</h1>
        </div>`;

    if (appState.currentUser) {
        const { currentUser } = appState;
        const isSuperAdmin = currentUser.role === 'superadmin';
        const isAdmin = currentUser.role === 'admin' || isSuperAdmin;
        const isStudent = currentUser.role === 'student'; // <<< ADICIONADO: Verifica se é aluno

        headerContent = `
            <div class="header-content">
                <h1 style="cursor: pointer;" onclick="window.AppHandlers.navigateTo('dashboard')">${logoHtml} ${appState.schoolProfile.name}</h1>
                <div class="user-info">
                    <span>Olá, ${currentUser.firstName}!</span>
                    <button class="action-button secondary" onclick="window.AppHandlers.handleNavigateToProfile(${currentUser.id})">Meu Perfil</button>
                    ${isStudent ? `<button class="action-button secondary" onclick="window.AppHandlers.handleNavigateToMyCertificates()">Meus Certificados</button>` : ''} 
                    ${isAdmin ? `<button class="action-button secondary" onclick="window.AppHandlers.handleNavigateToSchoolProfile()">Dados da UE</button>` : ''}
                    ${isSuperAdmin ? `<button class="action-button secondary" onclick="window.AppHandlers.handleNavigateToSystemSettings()">Configurações</button>` : ''}
                    <button onclick="window.AppHandlers.handleLogout()" class="logout-button">Sair</button>
                </div>
            </div>
        `;
    }
    headerElement.innerHTML = headerContent;
}