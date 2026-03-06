// src/views/auth.js

/** 
 * Renderiza a tela de login.
 * @returns {string} O HTML da tela de login.
 */
export function renderLoginScreen() {
  return `
    <div class="auth-container">
      <h2>Login</h2>
      <form id="login-form" onsubmit="window.AppHandlers.handleLogin(event)">
        <div class="form-group">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" required autocomplete="username" />
        </div>
        <div class="form-group">
          <label for="password">Senha</label>
          <input type="password" id="password" name="password" required autocomplete="current-password" />
        </div>
        <button type="submit" class="auth-button">Entrar</button>
        <p class="error-message" id="login-error" style="margin-top: 1rem"></p>
      </form>
      <div style="margin-top: 1rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem;">
        <button class="link-button" onclick="window.AppHandlers.navigateTo('forgotPasswordRequest')">Esqueci minha senha</button>
        <button class="link-button" onclick="window.AppHandlers.navigateTo('register')">Cadastre-se</button>
      </div>
    </div>
  `;
}

/**
 * Renderiza a tela de cadastro.
 * @returns {string} O HTML da tela de cadastro.
 */
export function renderRegisterScreen() {
  return `
    <div class="auth-container">
      <h2>Cadastro</h2>
      <form id="register-form" onsubmit="window.AppHandlers.handleRegister(event)">
        <div class="form-group">
          <label for="name">Nome</label>
          <input type="text" id="name" name="name" required autocomplete="given-name" />
        </div>
        <div class="form-group">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" required autocomplete="username" />
        </div>
        <div class="form-group">
          <label for="password">Senha</label>
          <input type="password" id="password" name="password" required autocomplete="new-password" minlength="6" />
        </div>
        <div class="form-group">
          <label for="confirmPassword">Confirmar Senha</label>
          <input type="password" id="confirmPassword" name="confirmPassword" required autocomplete="new-password" minlength="6" />
        </div>
        <button type="submit" class="auth-button">Cadastrar</button>
        <p class="error-message" id="register-error" style="margin-top: 1rem"></p>
      </form>
      <div style="margin-top: 1rem;">
        <button class="link-button" onclick="window.AppHandlers.navigateTo('login')">Voltar para o Login</button>
      </div>
    </div>
  `;
}

/**
 * Renderiza a tela de solicitação de redefinição de senha.
 * @returns {string} O HTML da tela de solicitação.
 */
export function renderForgotPasswordRequestScreen() {
  return `
    <div class="auth-container">
      <h2>Redefinir Senha</h2>
      <form id="forgot-password-form" onsubmit="window.AppHandlers.handleForgotPasswordRequest(event)">
        <div class="form-group">
          <label for="email">E-mail</label>
          <input type="email" id="email" name="email" required autocomplete="username" />
        </div>
        <button type="submit" class="auth-button">Enviar Link</button>
        <p class="error-message" id="forgot-password-error" style="margin-top: 1rem"></p>
      </form>
      <div style="margin-top: 1rem;">
        <button class="link-button" onclick="window.AppHandlers.navigateTo('login')">Voltar para o Login</button>
      </div>
    </div>
  `;
}

/**
 * Renderiza a tela de redefinição de senha (criar nova senha).
 * Recebe o token extraído da URL/hash.
 * @param {string} token Token para redefinição de senha.
 * @returns {string} O HTML da tela de redefinição de senha.
 */
export function renderResetPasswordScreen(token) {
  if (!token) {
    return `
      <div class="auth-container">
        <h2>Link Inválido</h2>
        <p>O link para redefinição de senha é inválido ou expirou.</p>
        <p>
          <button class="link-button" onclick="window.AppHandlers.navigateTo('forgotPasswordRequest')">Solicitar novo link</button>
        </p>
        <p>
          <button class="link-button" onclick="window.AppHandlers.navigateTo('login')">Voltar para o Login</button>
        </p>
      </div>
    `;
  }
  return `
    <div class="auth-container">
      <h2>Criar Nova Senha</h2>
      <form id="reset-password-form" onsubmit="window.AppHandlers.handleResetPassword(event)">
        <input type="hidden" name="token" value="${token}" />
        <div class="form-group">
          <label for="newPassword">Nova Senha (mínimo 6 caracteres)</label>
          <input type="password" id="newPassword" name="newPassword" required autocomplete="new-password" minlength="6" />
        </div>
        <div class="form-group">
          <label for="confirmPassword">Confirmar Nova Senha</label>
          <input type="password" id="confirmPassword" name="confirmPassword" required autocomplete="new-password" minlength="6" />
        </div>
        <button type="submit" class="auth-button">Redefinir Senha</button>
        <p class="error-message" id="reset-password-error" style="margin-top: 1rem"></p>
      </form>
      <p>
        <button class="link-button" onclick="window.AppHandlers.navigateTo('login')">Voltar para o Login</button>
      </p>
    </div>
  `;
}
