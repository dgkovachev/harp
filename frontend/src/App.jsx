import { useState } from 'react';

const registrationRoles = [
  {
    id: 'student',
    title: 'Student',
    description: 'Browse events and register quickly.'
  },
  {
    id: 'organizer',
    title: 'Organizer',
    description: 'Create and manage school events.'
  }
];

export default function App() {
  const [mode, setMode] = useState('sign-in');
  const [role, setRole] = useState('student');

  const isRegister = mode === 'register';

  return (
    <main className="auth-page">
      <div className="auth-glow auth-glow-left" aria-hidden="true" />
      <div className="auth-glow auth-glow-right" aria-hidden="true" />

      <section className="auth-shell" aria-labelledby="auth-title">
        <div className="brand-mark" aria-hidden="true">
          <span>♫</span>
        </div>

        <header className="brand-copy">
          <h1 id="auth-title">Harp login</h1>
          <p>School events, in perfect harmony.</p>
        </header>

        <section className="auth-card">
          <div className="mode-switch" role="tablist" aria-label="Authentication mode">
            <button
              type="button"
              className={mode === 'sign-in' ? 'mode-tab active' : 'mode-tab'}
              onClick={() => setMode('sign-in')}
              role="tab"
              aria-selected={mode === 'sign-in'}
            >
              Sign In
            </button>
            <button
              type="button"
              className={mode === 'register' ? 'mode-tab active' : 'mode-tab'}
              onClick={() => setMode('register')}
              role="tab"
              aria-selected={mode === 'register'}
            >
              Join Harp
            </button>
          </div>

          <form
            className="auth-form"
            onSubmit={(event) => {
              event.preventDefault();
            }}
          >
            <label>
              Username
              <input type="text" placeholder="e.g. ada.lovelace" autoComplete="username" />
            </label>

            <label>
              Password
              <input type="password" placeholder="••••••••" autoComplete={isRegister ? 'new-password' : 'current-password'} />
            </label>

            {isRegister ? (
              <>
                <label>
                  School Email
                  <input type="email" placeholder="you@school.edu" autoComplete="email" />
                </label>

                <label>
                  Grade / Year
                  <input type="text" placeholder="e.g. 10th grade, Junior" />
                </label>

                <div className="role-picker" role="radiogroup" aria-label="Join as">
                  <p className="section-label">I am joining as</p>
                  <div className="role-grid">
                    {registrationRoles.map((option) => (
                      <button
                        key={option.id}
                        type="button"
                        className={role === option.id ? 'role-card active' : 'role-card'}
                        onClick={() => setRole(option.id)}
                        aria-pressed={role === option.id}
                      >
                        <span className="role-icon" aria-hidden="true">
                          {option.id === 'student' ? '🎓' : '🗓'}
                        </span>
                        <strong>{option.title}</strong>
                        <span>{option.description}</span>
                      </button>
                    ))}
                  </div>
                </div>
              </>
            ) : null}

            <button type="submit" className="primary-action">
              {isRegister ? 'Join Harp' : 'Sign In'}
            </button>
          </form>

          <button
            type="button"
            className="auth-link"
            onClick={() => setMode(isRegister ? 'sign-in' : 'register')}
          >
            {isRegister ? 'Already have an account? Sign in' : 'New to Harp? Create an account'}
          </button>
        </section>

              <p className="connection-note">Connected to <code>harp</code></p>
      </section>
    </main>
  );
}