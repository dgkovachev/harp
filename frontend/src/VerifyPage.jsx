import { useState, useEffect } from 'react';

const API_URL = (import.meta.env.VITE_API_URL || '').replace(/\/+$/, '') || "http://localhost:8000";

export default function VerifyPage({ onBack }) {
  const [status, setStatus] = useState('loading');
  const [message, setMessage] = useState('');

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.get('verified') === '1' && params.get('token')) {
      const token = params.get('token');
      localStorage.setItem('harp_token', token);
      setStatus('verified');
    } else if (params.get('verify') === 'expired') {
      setStatus('expired');
    } else {
      setStatus('unknown');
    }
  }, []);

  const handleResend = async () => {
    const params = new URLSearchParams(window.location.search);
    const email = params.get('email');
    if (!email) {
      setMessage('Missing email. Please go back and register again.');
      return;
    }

    try {
      const res = await fetch(`${API_URL}/resend-verification`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
      });
      const data = await res.json();
      if (!data.success) {
        setMessage(data.error || 'Failed to resend');
        return;
      }
    } catch {
      setMessage('Network error. Try again later.');
      return;
    }

    setMessage('A new verification email has been sent. Check your inbox.');
  };

  return (
    <main className="auth-page">
      <button
        type="button"
        className="theme-toggle"
        onClick={() => onBack && onBack()}
        aria-label="Go back"
      >
        ←
      </button>
      <div className="auth-glow auth-glow-left" aria-hidden="true" />
      <div className="auth-glow auth-glow-right" aria-hidden="true" />

      <section className="auth-shell" aria-labelledby="verify-title">
        <div className="brand-mark" aria-hidden="true">
          <span>♫</span>
        </div>

        {status === 'loading' && (
          <>
            <header className="brand-copy">
              <h1 id="verify-title">Verifying...</h1>
              <p>Please wait while we verify your email.</p>
            </header>
          </>
        )}

        {status === 'verified' && (
          <>
            <header className="brand-copy">
              <h1 id="verify-title">Welcome to HARP!</h1>
              <p>Your email has been verified successfully.</p>
            </header>

            <section className="auth-card">
              <div style={{ textAlign: 'center', padding: '1.5rem' }}>
                <div style={{ fontSize: '3rem', marginBottom: '1rem' }}>🎉</div>
                <p style={{ fontSize: '1.1rem', color: 'var(--input-text)', margin: 0 }}>
                  You are now signed in. Start exploring events and clubs!
                </p>
                <button
                  type="button"
                  className="primary-action"
                  style={{ marginTop: '1.5rem' }}
                  onClick={() => window.location.href = '/'}
                >
                  Go to Home
                </button>
              </div>
            </section>
          </>
        )}

        {status === 'expired' && (
          <>
            <header className="brand-copy">
              <h1 id="verify-title">Link Expired</h1>
              <p>Your verification link is no longer valid.</p>
            </header>

            <section className="auth-card">
              <form
                className="auth-form"
                onSubmit={(e) => { e.preventDefault(); handleResend(); }}
              >
                <div style={{ textAlign: 'center', padding: '0.5rem 0' }}>
                  <p style={{ fontSize: '1.1rem', color: 'var(--input-text)', margin: 0 }}>
                    Click below to receive a new verification email.
                  </p>
                </div>

                {message && <span className="field-message form-success">{message}</span>}

                <button type="submit" className="primary-action">
                  Resend Verification Email
                </button>
              </form>

              <div className="auth-footer">
                <button
                  type="button"
                  className="auth-link"
                  onClick={onBack}
                >
                  Back to Sign In
                </button>
              </div>
            </section>
          </>
        )}

        {status === 'unknown' && (
          <>
            <header className="brand-copy">
              <h1 id="verify-title">Invalid Link</h1>
              <p>This verification link is missing required information.</p>
            </header>

            <section className="auth-card">
              <div className="auth-footer">
                <button
                  type="button"
                  className="auth-link"
                  onClick={onBack}
                >
                  Back to Sign In
                </button>
              </div>
            </section>
          </>
        )}

        <p className="connection-note">Connected to <code>harp</code></p>
      </section>
    </main>
  );
}
