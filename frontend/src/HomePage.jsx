import { useState, useEffect } from 'react';
import AdminPanel from './AdminPanel';

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
  const [eventsLoading, setEventsLoading] = useState(true);
  const [announcementsLoading, setAnnouncementsLoading] = useState(true);
  const [profile, setProfile] = useState(null);
  const [editing, setEditing] = useState(false);
  const [editName, setEditName] = useState('');
  const [editGrade, setEditGrade] = useState('');
  const [editMsg, setEditMsg] = useState('');
  const [tab, setTab] = useState('overview');
  const [theme, setTheme] = useState(() => localStorage.getItem('harp-theme') || 'light');
  const [loading, setLoading] = useState(true);
  const [adminMsg, setAdminMsg] = useState('');
  const [newEvent, setNewEvent] = useState({ title: '', description: '', event_date: '', location: '', max_capacity: '' });
  const [newClub, setNewClub] = useState({ club_name: '', description: '' });
  const [clubsLoading, setClubsLoading] = useState(true);
  const [allClubs, setAllClubs] = useState([]);
  const [clubMembers, setClubMembers] = useState([]);
  const [selectedClub, setSelectedClub] = useState('');
  const [myClubIds, setMyClubIds] = useState([]);
  const [clubMsg, setClubMsg] = useState('');
  const [joiningClub, setJoiningClub] = useState(null);
  const [registrations, setRegistrations] = useState([]);
  const [regsLoading, setRegsLoading] = useState(true);
  const [filterClub, setFilterClub] = useState(null);
  const [membersEvent, setMembersEvent] = useState(null);
  const [membersData, setMembersData] = useState(null);
  const [eventMsg, setEventMsg] = useState('');
  const [clubMembersEvent, setClubMembersEvent] = useState(null);
  const [clubMembersData, setClubMembersData] = useState(null);
  const [clubFilter, setClubFilter] = useState(null);
  const [announceFilter, setAnnounceFilter] = useState(null);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

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
    }).then(d => { if (d) setEvents(d.data || []); }).catch(() => {}).finally(() => setEventsLoading(false));

    window.getAnnouncements().then(d => { if (d.success) setAnnouncements(d.data || []); }).finally(() => setAnnouncementsLoading(false));

    fetch(`${API_URL}/users/me`, {
      headers: { 'Authorization': `Bearer ${token}` }
    }).then(r => r.json()).then(d => { if (d.success) setProfile(d.data); }).catch(() => {}).finally(() => setLoading(false));

    loadClubs();
    loadMyClubs();

    fetch(`${API_URL}/registrations/me`, {
      headers: { 'Authorization': `Bearer ${token}` }
    }).then(r => r.json()).then(d => { if (d.success) setRegistrations(d.data); }).catch(() => {}).finally(() => setRegsLoading(false));
  }, []);

  useEffect(() => {
    if (tab === 'admin' || tab === 'clubs') {
      loadClubs();
      loadMyClubs();
    }
  }, [tab]);

  const handleLogout = async () => {
    localStorage.removeItem('harp_clubs');
    localStorage.removeItem('harp_my_clubs');
    await window.logout();
    if (onLogout) onLogout();
  };

  const startEdit = () => {
    if (!profile) return;
    setEditName(profile.display_name);
    setEditGrade(profile.grade || '');
    setEditMsg('');
    setEditing(true);
  };

  const cancelEdit = () => {
    setEditing(false);
    setEditMsg('');
  };

  const saveProfile = async () => {
    if (!profile) return;
    setEditMsg('');
    const result = await window.updateUser(profile.user_id, { display_name: editName, grade: editGrade });
    if (result.success) {
      setProfile({ ...profile, display_name: editName, grade: editGrade });
      setEditing(false);
    } else {
      setEditMsg(result.error || 'Failed to update');
    }
  };

  const handleDeleteAccount = async () => {
    if (!profile) return;
    if (!window.confirm('Are you sure you want to delete your account? This cannot be undone.')) return;
    if (!window.confirm('All your events, registrations, and clubs will be permanently deleted. Continue?')) return;
    const result = await window.deleteUser(profile.user_id);
    if (result.success && onLogout) onLogout();
  };

  const getToken = () => localStorage.getItem('harp_token');

  const handleCreateEvent = async (e) => {
    e.preventDefault();
    setAdminMsg('');
    const res = await fetch(`${API_URL}/events`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${getToken()}` },
      body: JSON.stringify(newEvent)
    });
    const data = await res.json();
    if (data.success) {
      setAdminMsg('Event created!');
      setNewEvent({ title: '', description: '', event_date: '', location: '', max_capacity: '' });
    } else {
      setAdminMsg(data.error || 'Failed to create event');
    }
  };

  const handleCreateClub = async (e) => {
    e.preventDefault();
    setAdminMsg('');
    const res = await fetch(`${API_URL}/clubs`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${getToken()}` },
      body: JSON.stringify(newClub)
    });
    const data = await res.json();
    if (data.success) {
      setAdminMsg('Club created!');
      setNewClub({ club_name: '', description: '' });
    } else {
      setAdminMsg(data.error || 'Failed to create club');
    }
  };

  const loadClubs = async (force) => {
    const cached = localStorage.getItem('harp_clubs');
    if (cached && !force) {
      try { setAllClubs(JSON.parse(cached)); } catch {}
      setClubsLoading(false);
    } else {
      setClubsLoading(true);
    }
    const res = await fetch(`${API_URL}/clubs`, {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    });
    const d = await res.json();
    if (d.success) {
      setAllClubs(d.data || []);
      localStorage.setItem('harp_clubs', JSON.stringify(d.data || []));
    }
    setClubsLoading(false);
  };

  const loadMyClubs = async (force) => {
    const cached = localStorage.getItem('harp_my_clubs');
    if (cached && !force) {
      try { setMyClubIds(JSON.parse(cached)); } catch {}
    }
    const res = await fetch(`${API_URL}/clubs/me`, {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    });
    const d = await res.json();
    if (d.success) {
      const ids = d.data.map(c => c.club_id);
      setMyClubIds(ids);
      localStorage.setItem('harp_my_clubs', JSON.stringify(ids));
    }
  };

  const handleJoinClub = async (clubId) => {
    setJoiningClub(clubId);
    const res = await fetch(`${API_URL}/clubs/${clubId}/join`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${getToken()}` }
    });
    const d = await res.json();
    setClubMsg(d.error || 'Joined!');
    await loadMyClubs();
    await loadClubs();
    setJoiningClub(null);
  };

  const handleLeaveClub = async (clubId) => {
    setJoiningClub(clubId);
    const res = await fetch(`${API_URL}/clubs/${clubId}/leave`, {
      method: 'DELETE',
      headers: { 'Authorization': `Bearer ${getToken()}` }
    });
    const d = await res.json();
    setClubMsg(d.error || 'Left club');
    await loadMyClubs();
    await loadClubs();
    setJoiningClub(null);
  };

  const loadClubMembers = async (clubId) => {
    if (!clubId) { setClubMembers([]); return; }
    const res = await fetch(`${API_URL}/clubs/${clubId}/members`, {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    });
    const d = await res.json();
    if (d.success) setClubMembers(d.data || []);
  };

  const handleAssignLeader = async (userId) => {
    const res = await fetch(`${API_URL}/clubs/${selectedClub}/assign-leader`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${getToken()}` },
      body: JSON.stringify({ user_id: userId })
    });
    const d = await res.json();
    setAdminMsg(d.error || 'Leader assigned');
    loadClubMembers(selectedClub);
  };

  const toggleTheme = () => setTheme(t => t === 'light' ? 'dark' : 'light');

  const regMap = {};
  registrations.forEach(r => { regMap[r.event_id] = r; });

  const canViewMembers = (ev) => profile?.role === 'organizer' || profile?.user_id === ev.created_by;

  const handleJoinEvent = async (eventId) => {
    setEventMsg('');
    const res = await fetch(`${API_URL}/events/${eventId}/register`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${getToken()}` }
    });
    const d = await res.json();
    if (d.success) {
      setRegistrations(prev => [...prev.filter(r => r.event_id !== eventId), { event_id: eventId, ...d }]);
      setEvents(prev => prev.map(ev => ev.event_id === eventId ? {
        ...ev,
        confirmed_count: ev.confirmed_count + (d.status === 'confirmed' ? 1 : 0),
        waitlist_count: ev.waitlist_count + (d.status === 'waitlisted' ? 1 : 0)
      } : ev));
      setEventMsg(d.status === 'confirmed' ? 'Registered!' : 'Sent waitlist req');
    } else {
      setEventMsg(d.error || 'Failed to join');
    }
    setTimeout(() => setEventMsg(''), 3000);
  };

  const handleLeaveEvent = async (regId, eventId) => {
    setEventMsg('Leave request sent, you will be leaving in a bit…');
    const res = await fetch(`${API_URL}/registrations/${regId}`, {
      method: 'DELETE',
      headers: { 'Authorization': `Bearer ${getToken()}` }
    });
    const d = await res.json();
    if (d.success) {
      setRegistrations(prev => prev.map(r => r.registration_id === regId ? { ...r, status: 'cancelled' } : r));
      setEvents(prev => prev.map(ev => ev.event_id === eventId ? {
        ...ev,
        confirmed_count: Math.max(0, ev.confirmed_count - 1)
      } : ev));
      setEventMsg('Left event');
    } else {
      setEventMsg(d.error || 'Failed to leave');
    }
    setTimeout(() => setEventMsg(''), 3000);
  };

  const handleViewMembers = async (event) => {
    setMembersEvent(event);
    setMembersData(null);
    const token = getToken();
    const [confirmed, waitlist] = await Promise.all([
      fetch(`${API_URL}/events/${event.event_id}/registrations`, { headers: { 'Authorization': `Bearer ${token}` } }).then(r => r.json()),
      fetch(`${API_URL}/events/${event.event_id}/waitlist`, { headers: { 'Authorization': `Bearer ${token}` } }).then(r => r.json())
    ]);
    setMembersData({
      confirmed: confirmed.data || [],
      waitlist: waitlist.data || []
    });
  };

  const handleViewClubMembers = async (club) => {
    setClubMembersEvent(club);
    setClubMembersData(null);
    const res = await fetch(`${API_URL}/clubs/${club.club_id}/members`, {
      headers: { 'Authorization': `Bearer ${getToken()}` }
    });
    const d = await res.json();
    if (d.success) setClubMembersData(d.data || []);
  };

  return (
    <main className="home-page">
      <nav className="home-nav">
        <div className="home-nav-brand">
          <span className="home-nav-icon">♫</span>
          <span className="home-nav-title">HARP</span>
        </div>
        <div className="home-nav-tabs">
          <button className={`home-nav-tab ${tab === 'overview' ? 'active' : ''}`} onClick={() => { setTab('overview'); setMobileMenuOpen(false); }}>Overview</button>
          <button className={`home-nav-tab ${tab === 'events' ? 'active' : ''}`} onClick={() => { setTab('events'); setMobileMenuOpen(false); }}>Events</button>
          <button className={`home-nav-tab ${tab === 'announcements' ? 'active' : ''}`} onClick={() => { setTab('announcements'); setMobileMenuOpen(false); }}>Announcements</button>
          <button className={`home-nav-tab ${tab === 'clubs' ? 'active' : ''}`} onClick={() => { setTab('clubs'); setMobileMenuOpen(false); }}>Clubs</button>
          <button className={`home-nav-tab ${tab === 'profile' ? 'active' : ''}`} onClick={() => { setTab('profile'); setMobileMenuOpen(false); }}>Profile</button>
          {loading ? (
            <span className="home-nav-tab" style={{ opacity: 0.5, cursor: 'default' }}>Admin</span>
          ) : profile?.role === 'organizer' && (
            <button className={`home-nav-tab ${tab === 'admin' ? 'active' : ''}`} onClick={() => { setTab('admin'); setMobileMenuOpen(false); }}>Admin</button>
          )}
        </div>
        <div className="home-nav-actions">
          <button className="home-nav-theme" onClick={toggleTheme} aria-label="Toggle theme">
            {theme === 'light' ? '☾' : '☀'}
          </button>
          <button className="home-nav-hamburger" onClick={() => setMobileMenuOpen(o => !o)} aria-label="Menu">
            <span className={`home-nav-hamburger-line${mobileMenuOpen ? ' open' : ''}`}></span>
            <span className={`home-nav-hamburger-line${mobileMenuOpen ? ' open' : ''}`}></span>
            <span className={`home-nav-hamburger-line${mobileMenuOpen ? ' open' : ''}`}></span>
          </button>
          <button className="home-nav-logout" onClick={handleLogout}>Sign Out</button>
        </div>
        {mobileMenuOpen && (
          <div className="home-nav-mobile">
            <button className={`home-nav-tab${tab === 'overview' ? ' active' : ''}`} onClick={() => { setTab('overview'); setMobileMenuOpen(false); }}>Overview</button>
            <button className={`home-nav-tab${tab === 'events' ? ' active' : ''}`} onClick={() => { setTab('events'); setMobileMenuOpen(false); }}>Events</button>
            <button className={`home-nav-tab${tab === 'announcements' ? ' active' : ''}`} onClick={() => { setTab('announcements'); setMobileMenuOpen(false); }}>Announcements</button>
            <button className={`home-nav-tab${tab === 'clubs' ? ' active' : ''}`} onClick={() => { setTab('clubs'); setMobileMenuOpen(false); }}>Clubs</button>
            <button className={`home-nav-tab${tab === 'profile' ? ' active' : ''}`} onClick={() => { setTab('profile'); setMobileMenuOpen(false); }}>Profile</button>
            {!loading && profile?.role === 'organizer' && (
              <button className={`home-nav-tab${tab === 'admin' ? ' active' : ''}`} onClick={() => { setTab('admin'); setMobileMenuOpen(false); }}>Admin</button>
            )}
          </div>
        )}
      </nav>

      <div className="home-content">
        {tab === 'overview' && (
          <>
            <header className="home-welcome">
              <h1>Welcome{profile ? `, ${profile.display_name}` : ''}</h1>
              <p>Your school hub for events, clubs, and announcements.</p>
            </header>

            {(() => {
              const now = new Date();
              const upcoming = events.filter(ev => new Date(ev.event_date) >= now).slice(0, 1);
              const nextEv = upcoming[0];
              return nextEv ? (
                <div className="home-hero" onClick={() => setTab('events')}>
                  <div className="home-hero-info">
                    <span className="home-hero-label">Next Event</span>
                    <h2>{nextEv.title}</h2>
                    <p>{nextEv.description}</p>
                    <div className="home-hero-meta">
                      <span>📅 {new Date(nextEv.event_date).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</span>
                      <span>📍 {nextEv.location || 'TBD'}</span>
                      <span>👥 {nextEv.confirmed_count}/{nextEv.max_capacity} spots</span>
                    </div>
                  </div>
                  <div className="home-hero-countdown">
                    <span className="home-hero-days">{Math.ceil((new Date(nextEv.event_date) - now) / (1000 * 60 * 60 * 24))}</span>
                    <span className="home-hero-days-label">days away</span>
                  </div>
                </div>
              ) : null;
            })()}

            <div className="home-stats">
              <div className="home-stat-card">
                <span className="home-stat-icon">📋</span>
                <span className="home-stat-value">{events.length}</span>
                <span className="home-stat-label">Events</span>
              </div>
              <div className="home-stat-card">
                <span className="home-stat-icon">✅</span>
                <span className="home-stat-value">{registrations.filter(r => r.status === 'confirmed').length}</span>
                <span className="home-stat-label">Registered</span>
              </div>
              <div className="home-stat-card">
                <span className="home-stat-icon">⏳</span>
                <span className="home-stat-value">{registrations.filter(r => r.status === 'waitlisted').length}</span>
                <span className="home-stat-label">Waitlisted</span>
              </div>
              <div className="home-stat-card">
                <span className="home-stat-icon">🏛️</span>
                <span className="home-stat-value">{allClubs.length}</span>
                <span className="home-stat-label">Clubs</span>
              </div>
            </div>

            <div className="home-grid">
              <SectionCard title="Upcoming Events" onMore={() => setTab('events')}>
                {eventsLoading ? (
                  <p className="home-empty">Loading...</p>
                ) : events.length === 0 ? (
                  <p className="home-empty">No upcoming events.</p>
                ) : (
                  events.slice(0, 3).map(ev => {
                    const reg = regMap[ev.event_id];
                    return (
                    <div key={ev.event_id} className="home-event-item">
                      <strong>{ev.title}</strong>
                      <span>{ev.event_date?.slice(0, 10)} — {ev.confirmed_count}/{ev.max_capacity} spots{reg ? ` — ${reg.status}` : ''}</span>
                    </div>
                  );})
                )}
              </SectionCard>

              <SectionCard title="Announcements" onMore={() => setTab('announcements')}>
                {announcementsLoading ? (
                  <p className="home-empty">Loading...</p>
                ) : announcements.length === 0 ? (
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

              <SectionCard title="My Clubs" onMore={() => setTab('clubs')}>
                {clubsLoading ? (
                  <p className="home-empty">Loading...</p>
                ) : myClubIds.length === 0 ? (
                  <p className="home-empty">You are not a member of any club.</p>
                ) : (
                  allClubs.filter(c => myClubIds.includes(c.club_id)).slice(0, 3).map(c => (
                    <div key={c.club_id} className="home-event-item">
                      <strong>{c.club_name}</strong>
                      <span>{c.member_count} members</span>
                    </div>
                  ))
                )}
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

            {eventMsg && <span className="field-message form-success">{eventMsg}</span>}

            <div className="filter-bar">
              <button className={`filter-btn ${filterClub === null ? 'active' : ''}`} onClick={() => setFilterClub(null)}>All</button>
              {events.map(ev => (
                <button key={ev.event_id} className={`filter-btn ${filterClub === ev.event_id ? 'active' : ''}`} onClick={() => setFilterClub(ev.event_id)}>{ev.title}</button>
              ))}
            </div>

            <div className="home-list">
              {eventsLoading || regsLoading ? (
                <p className="home-empty">Loading events...</p>
              ) : events.length === 0 ? (
                <p className="home-empty">No events found.</p>
              ) : (() => {
                const filtered = events.filter(ev => !filterClub || Number(ev.event_id) === Number(filterClub));
                return filtered.length === 0 ? (
                  <p className="home-empty">No events found.</p>
                ) : filtered.map(ev => {
                const reg = regMap[ev.event_id];
                const club = allClubs.find(c => Number(c.club_id) === Number(ev.club_id));
                const isFull = ev.max_capacity && ev.confirmed_count >= ev.max_capacity;
                const pct = ev.max_capacity ? Math.round((ev.confirmed_count / ev.max_capacity) * 100) : 0;
                const evDate = new Date(ev.event_date);
                const now = new Date();
                const daysUntil = Math.ceil((evDate - now) / (1000 * 60 * 60 * 24));
                const dateLabel = daysUntil < 0 ? 'Past' : daysUntil === 0 ? 'Today' : daysUntil === 1 ? 'Tomorrow' : `${daysUntil}d`;
                return (
                  <div key={ev.event_id} className="home-list-item">
                    <div className="evt-date-badge" data-theme={daysUntil < 0 ? 'past' : daysUntil <= 3 ? 'soon' : 'upcoming'}>
                      <span className="evt-date-day">{evDate.getDate()}</span>
                      <span className="evt-date-mon">{evDate.toLocaleString('en', { month: 'short' })}</span>
                      <span className="evt-date-label">{dateLabel}</span>
                    </div>
                    <div className="home-list-item-content">
                      {club && <span className="event-club-badge">{club.club_name}</span>}
                      <strong>{ev.title}</strong>
                      <p>{ev.description}</p>
                      <small>{ev.location && `📍 ${ev.location}`}</small>
                      <div className="evt-cap-bar">
                        <div className="evt-cap-track">
                          <div className="evt-cap-fill" style={{ width: `${Math.min(pct, 100)}%` }}></div>
                        </div>
                        <span className="evt-cap-label">{ev.confirmed_count}/{ev.max_capacity}</span>
                      </div>
                      {reg && (
                        <span className={`status-badge status-${reg.status}`}>
                          {reg.status === 'confirmed' ? '✅ Confirmed' : reg.status === 'waitlisted' ? `⏳ Queued (#${reg.queue_position})` : 'Cancelled'}
                        </span>
                      )}
                    </div>
                    <div className="home-list-actions">
                      {!reg && <button className="btn-primary" onClick={() => handleJoinEvent(ev.event_id)}>{isFull ? 'Join Waitlist' : 'Join'}</button>}
                      {reg && (reg.status === 'confirmed' || reg.status === 'waitlisted') && (
                        <button className="btn-danger" onClick={() => handleLeaveEvent(reg.registration_id, ev.event_id)}>Leave</button>
                      )}
                      {reg && reg.status === 'cancelled' && (
                        <button className="btn-primary" onClick={() => handleJoinEvent(ev.event_id)}>{isFull ? 'Rejoin Waitlist' : 'Rejoin'}</button>
                      )}
                      {canViewMembers(ev) && (
                        <button className="btn-outline" onClick={() => handleViewMembers(ev)}>Members</button>
                      )}
                    </div>
                  </div>
                );
              });})()}
            </div>

            {membersEvent && membersData && (
              <div className="modal-overlay" onClick={() => { setMembersEvent(null); setMembersData(null); }}>
                <div className="modal-content" onClick={e => e.stopPropagation()}>
                  <h2>{membersEvent.title} — Members</h2>
                  <button className="modal-close" onClick={() => { setMembersEvent(null); setMembersData(null); }}>×</button>
                  <h3 style={{ marginTop: 0 }}>Confirmed ({membersData.confirmed.length})</h3>
                  {membersData.confirmed.length === 0 ? <p className="home-empty">None</p> : (
                    <table className="members-table">
                      <thead><tr><th>#</th><th>Name</th><th>Email</th></tr></thead>
                      <tbody>
                        {membersData.confirmed.map((m, i) => (
                          <tr key={i}><td>{i+1}</td><td>{m.display_name}</td><td>{m.user_email}</td></tr>
                        ))}
                      </tbody>
                    </table>
                  )}
                  <h3>Waitlist ({membersData.waitlist.length})</h3>
                  {membersData.waitlist.length === 0 ? <p className="home-empty">None</p> : (
                    <table className="members-table">
                      <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Queue</th></tr></thead>
                      <tbody>
                        {membersData.waitlist.map((m, i) => (
                          <tr key={i}><td>{i+1}</td><td>{m.display_name}</td><td>{m.user_email}</td><td>#{m.queue_position}</td></tr>
                        ))}
                      </tbody>
                    </table>
                  )}
                </div>
              </div>
            )}
          </>
        )}

        {tab === 'announcements' && (
          <>
            <header className="home-welcome">
              <h1>Announcements</h1>
              <p>School announcements and updates.</p>
            </header>
            <div className="filter-bar">
              <button className={`filter-btn ${announceFilter === null ? 'active' : ''}`} onClick={() => setAnnounceFilter(null)}>All</button>
              <button className={`filter-btn ${announceFilter === 'school' ? 'active' : ''}`} onClick={() => setAnnounceFilter('school')}>School</button>
              {allClubs.map(c => (
                <button key={c.club_id} className={`filter-btn ${announceFilter === `club:${c.club_id}` ? 'active' : ''}`} onClick={() => setAnnounceFilter(`club:${c.club_id}`)}>{c.club_name}</button>
              ))}
            </div>
            <div className="home-list">
              {announcementsLoading ? (
                <p className="home-empty">Loading announcements...</p>
              ) : announcements.length === 0 ? (
                <p className="home-empty">No announcements.</p>
              ) : (() => {
                const filtered = announcements.filter(a => {
                  if (!announceFilter) return true;
                  if (announceFilter === 'school') return a.category === 'general';
                  if (announceFilter.startsWith('club:')) {
                    const clubId = announceFilter.split(':')[1];
                    return a.category === 'club' && Number(a.club_id) === Number(clubId);
                  }
                  return true;
                });
                return filtered.length === 0 ? (
                  <p className="home-empty">No announcements found.</p>
                ) : filtered.map(a => {
                const club = a.category === 'club' ? allClubs.find(c => Number(c.club_id) === Number(a.club_id)) : null;
                return (
                <div key={a.announcement_id} className="home-list-item">
                  <div className="ann-cat-badge" data-cat={a.category}>{a.category === 'general' ? '📢 School' : a.category === 'club' ? '🏛️ Club' : '📅 Event'}</div>
                  <div>
                    {club && <span className="event-club-badge">{club.club_name}</span>}
                    <strong>{a.title}</strong>
                    <p>{a.body}</p>
                    <small>{a.created_at?.slice(0, 10)}</small>
                  </div>
                </div>
              );});})()}
            </div>
          </>
        )}

        {tab === 'clubs' && (
          <>
            <header className="home-welcome">
              <h1>Clubs</h1>
              <p>Discover and join school clubs.</p>
            </header>
            {clubMsg && <span className="field-message form-success">{clubMsg}</span>}

            <div className="filter-bar">
              <button className={`filter-btn ${clubFilter === null ? 'active' : ''}`} onClick={() => setClubFilter(null)}>All</button>
              {allClubs.map(c => (
                <button key={c.club_id} className={`filter-btn ${clubFilter === c.club_id ? 'active' : ''}`} onClick={() => setClubFilter(c.club_id)}>{c.club_name}</button>
              ))}
            </div>

            <div className="home-list">
              {clubsLoading ? (
                <p className="home-empty">Loading clubs...</p>
              ) : allClubs.length === 0 ? (
                <p className="home-empty">No clubs yet.</p>
              ) : (() => {
                const filtered = allClubs.filter(c => !clubFilter || Number(c.club_id) === Number(clubFilter));
                return filtered.length === 0 ? (
                  <p className="home-empty">No clubs found.</p>
                ) : filtered.map(c => {
                const isMember = myClubIds.includes(c.club_id);
                const isPending = !c.is_approved;
                return (
                  <div key={c.club_id} className="home-list-item">
                    <div className="club-icon">{c.club_name.charAt(0).toUpperCase()}</div>
                    <div>
                      <strong>{c.club_name}</strong>
                      <p>{c.description || 'No description'}</p>
                      <div className="evt-cap-bar">
                        <div className="evt-cap-track">
                          <div className="evt-cap-fill club-fill" style={{ width: `${Math.min(c.member_count * 5, 100)}%` }}></div>
                        </div>
                        <span className="evt-cap-label">{c.member_count} members</span>
                      </div>
                      <small>{c.owner_name || 'Unknown'}{isPending ? ' — ⏳ Pending approval' : ''}</small>
                    </div>
                    <div className="home-list-actions">
                      {joiningClub === c.club_id ? (
                        <span className="home-empty">...</span>
                      ) : isMember ? (
                        <>
                          <button className="btn-outline" onClick={() => handleViewClubMembers(c)}>👥</button>
                          <button className="home-card-more" style={{color:'#e74c3c'}} onClick={() => handleLeaveClub(c.club_id)}>Leave</button>
                        </>
                      ) : isPending ? (
                        <span className="home-empty">⏳ Pending</span>
                      ) : (
                        <>
                          <button className="btn-outline" onClick={() => handleViewClubMembers(c)}>👥</button>
                          <button className="home-card-more" onClick={() => handleJoinClub(c.club_id)}>Join</button>
                        </>
                      )}
                    </div>
                  </div>
                );
              });})()}
            </div>

            {clubMembersEvent && (
              <div className="modal-overlay" onClick={() => { setClubMembersEvent(null); setClubMembersData(null); }}>
                <div className="modal-content" onClick={e => e.stopPropagation()}>
                  <h2>{clubMembersEvent.club_name} — Members</h2>
                  <button className="modal-close" onClick={() => { setClubMembersEvent(null); setClubMembersData(null); }}>×</button>
                  {!clubMembersData ? (
                    <p className="home-empty">Loading...</p>
                  ) : clubMembersData.length === 0 ? (
                    <p className="home-empty">No members.</p>
                  ) : (
                    <table className="members-table">
                      <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th></tr></thead>
                      <tbody>
                        {clubMembersData.map((m, i) => (
                          <tr key={m.user_id}><td>{i+1}</td><td>{m.display_name}</td><td>{m.user_email}</td><td>{m.role}</td></tr>
                        ))}
                      </tbody>
                    </table>
                  )}
                </div>
              </div>
            )}
          </>
        )}

        {tab === 'admin' && loading && (
          <div className="home-content-loading">
            <p>Loading admin panel...</p>
          </div>
        )}
        {tab === 'admin' && !loading && profile?.role === 'organizer' && (
          <AdminPanel API_URL={API_URL} getToken={() => localStorage.getItem('harp_token')} />
        )}

        {tab === 'profile' && (
          <>
            <header className="home-welcome">
              <h1>Account</h1>
              <p>Your profile and account settings.</p>
            </header>
            <div className="home-grid">
              <div className="home-card">
                <div className="home-card-header">
                  <h2>Profile</h2>
                  {profile && !editing && <button className="home-card-more" onClick={startEdit}>Edit</button>}
                </div>
                <div className="home-card-body">
                  {!profile ? (
                    <p className="home-empty">Loading profile...</p>
                  ) : editing ? (
                    <div className="profile-edit-form">
                      <label>Name
                        <input type="text" value={editName} onChange={e => setEditName(e.target.value)} />
                      </label>
                      <label>Grade
                        <input type="text" value={editGrade} onChange={e => setEditGrade(e.target.value)} />
                      </label>
                      {editMsg && <span className="field-message form-error">{editMsg}</span>}
                      <div className="profile-edit-actions">
                        <button className="primary-action" onClick={saveProfile}>Save</button>
                        <button className="home-card-more" onClick={cancelEdit}>Cancel</button>
                      </div>
                    </div>
                  ) : (
                    <div className="profile-details">
                      <div className="profile-row"><strong>Name</strong><span>{profile.display_name}</span></div>
                      <div className="profile-row"><strong>Email</strong><span>{profile.user_email}</span></div>
                      <div className="profile-row"><strong>Role</strong><span className="role-badge">{profile.role}</span></div>
                      <div className="profile-row"><strong>Grade</strong><span>{profile.grade || '—'}</span></div>
                      <div className="profile-row"><strong>Verified</strong><span>{profile.is_verified ? 'Yes' : 'No'}</span></div>
                      <div className="profile-row"><strong>Joined</strong><span>{profile.created_at?.slice(0, 10)}</span></div>
                    </div>
                  )}
                </div>
              </div>

              <div className="home-card">
                <div className="home-card-header">
                  <h2>Danger Zone</h2>
                </div>
                <div className="home-card-body">
                  <p className="home-empty">Permanently delete your account and all associated data.</p>
                  <button className="danger-action" onClick={handleDeleteAccount}>Delete Account</button>
                </div>
              </div>
            </div>
          </>
        )}
      </div>
    </main>
  );
}
