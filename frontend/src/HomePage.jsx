import { useState, useEffect } from 'react';

const API_URL = (import.meta.env.VITE_API_URL || '').replace(/\/+$/, '') || "http://localhost:8000";

function SectionCard({ title, children, onMore }) {
  return (
    <div className="home-card">
      <div className="home-card-header">
        <h2>{title}</h2>
        {onMore && <button className="home-card-more" onClick={onMore}>View All</button>}
      </div>
      <div className="home-card-body">
        {children}
      </div>
    </div>
  );
}

export default function HomePage({ onLogout }) {
  const [events, setEvents] = useState([]);
  const [announcements, setAnnouncements] = useState([]);
  const [tab, setTab] = useState('overview');
  const [theme, setTheme] = useState(() => localStorage.getItem('harp-theme') || 'light');

  useEffect(() => {
    document.documentElement.dataset.theme = theme;
    localStorage.setItem('harp-theme', theme);
  }, [theme]);

  useEffect(() => {
    const token = localStorage.getItem('harp_token');
    if (!token) return;

    fetch(`${API_URL}/events`, {
      headers: { 'Authorization': `Bearer ${token}` }
    }).then(r => {
      if (r.status === 401) { onLogout(); return null; }
      return r.json();
    }).then(d => { if (d) setEvents(d.data || []); }).catch(() => {});

    window.getAnnouncements().then(d => { if (d.success) setAnnouncements(d.data || []); });
  }, []);

  const handleLogout = async () => {
    await window.logout();
    if (onLogout) onLogout();
  };

  const toggleTheme = () => setTheme(t => t === 'light' ? 'dark' : 'light');

  return (
    <main className="home-page">
      <nav className="home-nav">
        <div className="home-nav-brand">
          <span className="home-nav-icon">♫</span>
          <span className="home-nav-title">HARP</span>
        </div>
        <div className="home-nav-tabs">
          <button className={`home-nav-tab ${tab === 'overview' ? 'active' : ''}`} onClick={() => setTab('overview')}>Overview</button>
          <button className={`home-nav-tab ${tab === 'events' ? 'active' : ''}`} onClick={() => setTab('events')}>Events</button>
          <button className={`home-nav-tab ${tab === 'announcements' ? 'active' : ''}`} onClick={() => setTab('announcements')}>Announcements</button>
          <button className={`home-nav-tab ${tab === 'clubs' ? 'active' : ''}`} onClick={() => setTab('clubs')}>Clubs</button>
        </div>
        <div className="home-nav-actions">
          <button className="home-nav-theme" onClick={toggleTheme} aria-label="Toggle theme">
            {theme === 'light' ? '☾' : '☀'}
          </button>
          <button className="home-nav-logout" onClick={handleLogout}>Sign Out</button>
        </div>
      </nav>

      <div className="home-content">
        {tab === 'overview' && (
          <>
            <header className="home-welcome">
              <h1>Dashboard</h1>
              <p>Upcoming events, announcements, and more.</p>
            </header>

            <div className="home-grid">
              <SectionCard title="Upcoming Events" onMore={() => setTab('events')}>
                {events.length === 0 ? (
                  <p className="home-empty">No upcoming events.</p>
                ) : (
                  events.slice(0, 3).map(ev => (
                    <div key={ev.event_id} className="home-event-item">
                      <strong>{ev.title}</strong>
                      <span>{ev.event_date?.slice(0, 10)} — {ev.confirmed_count}/{ev.max_capacity} registered</span>
                    </div>
                  ))
                )}
              </SectionCard>

              <SectionCard title="Announcements" onMore={() => setTab('announcements')}>
                {announcements.length === 0 ? (
                  <p className="home-empty">No announcements yet.</p>
                ) : (
                  announcements.slice(0, 3).map(a => (
                    <div key={a.announcement_id} className="home-announcement-item">
                      <strong>{a.title}</strong>
                      <span>{a.body?.slice(0, 80)}...</span>
                    </div>
                  ))
                )}
              </SectionCard>

              <SectionCard title="My Clubs">
                <p className="home-empty">You are not a member of any club.</p>
              </SectionCard>
            </div>
          </>
        )}

        {tab === 'events' && (
          <>
            <header className="home-welcome">
              <h1>Events</h1>
              <p>Browse and register for school events.</p>
            </header>
            <div className="home-list">
              {events.length === 0 ? (
                <p className="home-empty">No events found.</p>
              ) : events.map(ev => (
                <div key={ev.event_id} className="home-list-item">
                  <div>
                    <strong>{ev.title}</strong>
                    <p>{ev.description}</p>
                    <small>{ev.event_date?.slice(0, 10)} — {ev.location} — {ev.confirmed_count}/{ev.max_capacity} spots</small>
                  </div>
                </div>
              ))}
            </div>
          </>
        )}

        {tab === 'announcements' && (
          <>
            <header className="home-welcome">
              <h1>Announcements</h1>
              <p>School announcements and updates.</p>
            </header>
            <div className="home-list">
              {announcements.length === 0 ? (
                <p className="home-empty">No announcements.</p>
              ) : announcements.map(a => (
                <div key={a.announcement_id} className="home-list-item">
                  <div>
                    <strong>{a.title}</strong>
                    <p>{a.body}</p>
                    <small>{a.created_at?.slice(0, 10)}</small>
                  </div>
                </div>
              ))}
            </div>
          </>
        )}

        {tab === 'clubs' && (
          <>
            <header className="home-welcome">
              <h1>Clubs</h1>
              <p>Discover and join school clubs.</p>
            </header>
            <div className="home-list">
              <p className="home-empty">Club features coming soon.</p>
            </div>
          </>
        )}
      </div>
    </main>
  );
}
