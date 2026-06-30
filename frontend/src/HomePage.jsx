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
          <button className={`home-nav-tab ${tab === 'profile' ? 'active' : ''}`} onClick={() => setTab('profile')}>Profile</button>
          {loading ? (
            <span className="home-nav-tab" style={{ opacity: 0.5, cursor: 'default' }}>Admin</span>
          ) : profile?.role === 'organizer' && (
            <button className={`home-nav-tab ${tab === 'admin' ? 'active' : ''}`} onClick={() => setTab('admin')}>Admin</button>
          )}
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
                {eventsLoading ? (
                  <p className="home-empty">Loading...</p>
                ) : events.length === 0 ? (
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
            <div className="home-list">
              {eventsLoading ? (
                <p className="home-empty">Loading events...</p>
              ) : events.length === 0 ? (
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
              {announcementsLoading ? (
                <p className="home-empty">Loading announcements...</p>
              ) : announcements.length === 0 ? (
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
            {clubMsg && <span className="field-message form-success">{clubMsg}</span>}
            <div className="home-list">
              {clubsLoading ? (
                <p className="home-empty">Loading clubs...</p>
              ) : allClubs.length === 0 ? (
                <p className="home-empty">No clubs yet.</p>
              ) : allClubs.map(c => {
                const isMember = myClubIds.includes(c.club_id);
                const isPending = !c.is_approved;
                return (
                  <div key={c.club_id} className="home-list-item">
                    <div>
                      <strong>{c.club_name}</strong>
                      <p>{c.description || 'No description'}</p>
                      <small>{c.member_count} members — {c.owner_name || 'Unknown'}{isPending ? ' — Pending approval' : ''}</small>
                    </div>
                    <div className="home-list-actions">
                      {joiningClub === c.club_id ? (
                        <span className="home-empty">...</span>
                      ) : isMember ? (
                        <button className="home-card-more" style={{color:'#e74c3c'}} onClick={() => handleLeaveClub(c.club_id)}>Leave</button>
                      ) : isPending ? (
                        <span className="home-empty">Pending</span>
                      ) : (
                        <button className="home-card-more" onClick={() => handleJoinClub(c.club_id)}>Join</button>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
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
