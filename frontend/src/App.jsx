import { useState, useEffect, useMemo } from 'react';
import HelpPage from './HelpPage';

const API_URL = import.meta.env.VITE_API_URL;

const registrationRoles = [
  {
    id: 'student',
    title: 'Student',
    description: 'Browse events and register quickly.'
  },
  {
    id: 'organizer',
    title: 'School Organizer',
    description: 'Create and manage school events.'
  }
];

function NoteParticles() {
  const configs = useMemo(() => [
    { char: '♪', left: '4%', size: '2rem', duration: '16s', delay: '0s' },
    { char: '♫', left: '12%', size: '2.6rem', duration: '22s', delay: '2s' },
    { char: '♩', left: '22%', size: '1.6rem', duration: '14s', delay: '5s' },
    { char: '♬', left: '32%', size: '2.2rem', duration: '20s', delay: '1s' },
    { char: '♫', left: '42%', size: '1.8rem', duration: '18s', delay: '7s' },
    { char: '♪', left: '50%', size: '2.4rem', duration: '24s', delay: '3s' },
    { char: '♩', left: '58%', size: '1.5rem', duration: '15s', delay: '9s' },
    { char: '♬', left: '66%', size: '2rem', duration: '19s', delay: '4s' },
    { char: '♫', left: '75%', size: '2.8rem', duration: '26s', delay: '0s' },
    { char: '♪', left: '83%', size: '1.7rem', duration: '17s', delay: '6s' },
    { char: '♩', left: '91%', size: '2.1rem', duration: '21s', delay: '8s' },
    { char: '♬', left: '97%', size: '1.4rem', duration: '13s', delay: '2s' },
    { char: '♫', left: '8%', size: '3rem', duration: '28s', delay: '10s' },
    { char: '♪', left: '28%', size: '2.3rem', duration: '23s', delay: '4s' },
    { char: '♩', left: '48%', size: '1.9rem', duration: '16s', delay: '1s' },
    { char: '♬', left: '62%', size: '2.5rem', duration: '25s', delay: '6s' },
    { char: '♫', left: '72%', size: '1.6rem', duration: '14s', delay: '3s' },
    { char: '♪', left: '88%', size: '2.2rem', duration: '20s', delay: '9s' },
    { char: '♩', left: '16%', size: '2.7rem', duration: '27s', delay: '0s' },
    { char: '♬', left: '38%', size: '1.3rem', duration: '12s', delay: '7s' },
  ], []);

  return (
    <>
      {configs.map((c, i) => (
        <span
          key={i}
          className="note-particle"
          aria-hidden="true"
          style={{
            left: c.left,
            bottom: '-3rem',
            fontSize: c.size,
            animationDuration: c.duration,
            animationDelay: c.delay,
          }}
        >
          {c.char}
        </span>
      ))}
    </>
  );
}

export default function App() {

  useEffect(() => {
const token = () => localStorage.getItem('harp_token');

window.login = async (email, password) => {
  const res = await fetch(`${API_URL}/index.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ user_email: email, password })
  });
  const data = await res.json();
  if (data.token) localStorage.setItem('harp_token', data.token);
  return data;
};

window.register = async (email, name, password) => {
  const res = await fetch(`${API_URL}/index.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ user_email: email, display_name: name, password })
  });
  const data = await res.json();
  if (data.token) localStorage.setItem('harp_token', data.token);
  return data;
};

window.getUser = async (id) => {
  const res = await fetch(`${API_URL}/index.php?id=${id}`, {
    headers: { 'Authorization': `Bearer ${token()}` }
  });
  return res.json();
};

window.deleteUser = async (id) => {
  const res = await fetch(`${API_URL}/index.php`, {
    method: 'DELETE',
    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token()}` },
    body: JSON.stringify({ user_id: id })
  });
  return res.json();
};

window.logout = () => {
  localStorage.removeItem('harp_token');
};
  }, []);






  const [mode, setMode] = useState('sign-in');
  const [role, setRole] = useState('student');
  const [page, setPage] = useState('auth');
  const [theme, setTheme] = useState(() => localStorage.getItem('harp-theme') || 'light');

  const isRegister = mode === 'register';

  useEffect(() => {
    document.documentElement.dataset.theme = theme;
    localStorage.setItem('harp-theme', theme);
  }, [theme]);

  const toggleTheme = () => setTheme((t) => (t === 'light' ? 'dark' : 'light'));

  if (page === 'help') {
    return <HelpPage onBack={() => setPage('auth')} theme={theme} onToggleTheme={toggleTheme} />;
  }

  return (
    <main className="auth-page">
      <NoteParticles />
      <button
        type="button"
        className="theme-toggle"
        onClick={toggleTheme}
        aria-label={theme === 'light' ? 'Switch to dark mode' : 'Switch to light mode'}
      >
        {theme === 'light' ? '☾' : '☀'}
      </button>
      <div className="auth-glow auth-glow-left" aria-hidden="true" />
      <div className="auth-glow auth-glow-right" aria-hidden="true" />

      <section className="auth-shell" aria-labelledby="auth-title">
        <div className="brand-mark" aria-hidden="true">
          <span>♫</span>
        </div>

        <header className="brand-copy">
          <h1 id="auth-title">Welcome to HARP</h1>
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
                  School Code
                  <input type="text" placeholder="e.g. HARP-2024" autoComplete="off" />
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

          <div className="auth-footer">
            <button
              type="button"
              className="auth-link"
              onClick={() => setMode(isRegister ? 'sign-in' : 'register')}
            >
              {isRegister ? 'Already have an account? Sign in' : 'New to Harp? Create an account'}
            </button>
            <button
              type="button"
              className="auth-link help-link"
              onClick={() => setPage('help')}
            >
              ?
            </button>
          </div>
        </section>

        <p className="connection-note">Connected to <code>harp</code></p>
      </section>
    </main>
  );
}
