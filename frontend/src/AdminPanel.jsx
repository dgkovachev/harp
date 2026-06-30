import { useState, useEffect } from 'react';

export default function AdminPanel({ API_URL, getToken }) {
  const [msg, setMsg] = useState('');
  const [loading, setLoading] = useState(true);

  const [newEvent, setNewEvent] = useState({ title: '', description: '', event_date: '', location: '', max_capacity: '' });
  const [newClub, setNewClub] = useState({ club_name: '', description: '' });
  const [newAnnouncement, setNewAnnouncement] = useState({ title: '', body: '', category: 'general', club_id: '', event_id: '' });

  const [events, setEvents] = useState([]);
  const [clubs, setClubs] = useState([]);
  const [announcements, setAnnouncements] = useState([]);

  const [clubMembers, setClubMembers] = useState([]);
  const [selectedClub, setSelectedClub] = useState('');

  const [editingEvent, setEditingEvent] = useState(null);
  const [editingClub, setEditingClub] = useState(null);
  const [editingAnnouncement, setEditingAnnouncement] = useState(null);
  const [viewClubMembers, setViewClubMembers] = useState(null);
  const [viewClubMembersData, setViewClubMembersData] = useState(null);

  useEffect(() => {
    loadAll();
  }, []);

  const loadAll = async () => {
    setLoading(true);
    const token = getToken();
    const headers = { 'Authorization': `Bearer ${token}` };

    const [eRes, cRes, aRes] = await Promise.all([
      fetch(`${API_URL}/events`, { headers }),
      fetch(`${API_URL}/clubs`, { headers }),
      fetch(`${API_URL}/announcements`, { headers }),
    ]);

    const eData = await eRes.json();
    const cData = await cRes.json();
    const aData = await aRes.json();

    if (eData.success) setEvents(eData.data || []);
    if (cData.success) setClubs(cData.data || []);
    if (aData.success) setAnnouncements(aData.data || []);
    setLoading(false);
  };

  const api = async (url, opts = {}) => {
    const res = await fetch(url, {
      headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${getToken()}`, ...opts.headers },
      ...opts,
    });
    return res.json();
  };

  const handleCreateEvent = async (e) => {
    e.preventDefault();
    setMsg('');
    const d = await api(`${API_URL}/events`, { method: 'POST', body: JSON.stringify(newEvent) });
    setMsg(d.success ? 'Event created!' : (d.error || 'Failed'));
    if (d.success) { setNewEvent({ title: '', description: '', event_date: '', location: '', max_capacity: '' }); loadAll(); }
  };

  const handleUpdateEvent = async (id) => {
    setMsg('');
    const d = await api(`${API_URL}/events/${id}`, { method: 'PUT', body: JSON.stringify(editingEvent) });
    setMsg(d.success ? 'Event updated!' : (d.error || 'Failed'));
    if (d.success) { setEditingEvent(null); loadAll(); }
  };

  const handleDeleteEvent = async (id) => {
    if (!window.confirm('Delete this event? Registrations will be lost.')) return;
    setMsg('');
    const d = await api(`${API_URL}/events/${id}/cancel`, { method: 'POST' });
    setMsg(d.success ? 'Event deleted!' : (d.error || 'Failed'));
    if (d.success) loadAll();
  };

  const handleCreateClub = async (e) => {
    e.preventDefault();
    setMsg('');
    const d = await api(`${API_URL}/clubs`, { method: 'POST', body: JSON.stringify(newClub) });
    setMsg(d.success ? 'Club created!' : (d.error || 'Failed'));
    if (d.success) { setNewClub({ club_name: '', description: '' }); loadAll(); }
  };

  const handleUpdateClub = async (id) => {
    setMsg('');
    const d = await api(`${API_URL}/clubs/${id}`, { method: 'PUT', body: JSON.stringify(editingClub) });
    setMsg(d.success ? 'Club updated!' : (d.error || 'Failed'));
    if (d.success) { setEditingClub(null); loadAll(); }
  };

  const handleDeleteClub = async (id) => {
    if (!window.confirm('Delete this club? This cannot be undone.')) return;
    setMsg('');
    const d = await api(`${API_URL}/clubs/${id}`, { method: 'DELETE' });
    setMsg(d.success ? 'Club deleted!' : (d.error || 'Failed'));
    if (d.success) loadAll();
  };

  const handleCreateAnnouncement = async (e) => {
    e.preventDefault();
    setMsg('');
    const d = await api(`${API_URL}/createAnnouncement`, { method: 'POST', body: JSON.stringify(newAnnouncement) });
    setMsg(d.success ? 'Announcement created!' : (d.error || 'Failed'));
    if (d.success) { setNewAnnouncement({ title: '', body: '', category: 'general', club_id: '', event_id: '' }); loadAll(); }
  };

  const handleUpdateAnnouncement = async (id) => {
    setMsg('');
    const d = await api(`${API_URL}/announcement/${id}`, { method: 'PUT', body: JSON.stringify(editingAnnouncement) });
    setMsg(d.success ? 'Announcement updated!' : (d.error || 'Failed'));
    if (d.success) { setEditingAnnouncement(null); loadAll(); }
  };

  const handleDeleteAnnouncement = async (id) => {
    if (!window.confirm('Delete this announcement?')) return;
    setMsg('');
    const d = await api(`${API_URL}/announcement/${id}`, { method: 'DELETE' });
    setMsg(d.success ? 'Announcement deleted!' : (d.error || 'Failed'));
    if (d.success) loadAll();
  };

  const loadClubMembers = async (clubId) => {
    if (!clubId) { setClubMembers([]); return; }
    const d = await api(`${API_URL}/clubs/${clubId}/members`);
    if (d.success) setClubMembers(d.data || []);
  };

  const handleAssignLeader = async (userId) => {
    const d = await api(`${API_URL}/clubs/${selectedClub}/assign-leader`, { method: 'POST', body: JSON.stringify({ user_id: userId }) });
    setMsg(d.error || 'Leader assigned');
    loadClubMembers(selectedClub);
  };

  const handleViewClubMembers = async (club) => {
    setViewClubMembers(club);
    setViewClubMembersData(null);
    const d = await api(`${API_URL}/clubs/${club.club_id}/members`);
    if (d.success) setViewClubMembersData(d.data || []);
  };

  const handleLeaveClub = async (clubId) => {
    setMsg('');
    const d = await api(`${API_URL}/clubs/${clubId}/leave`, { method: 'DELETE' });
    setMsg(d.success ? 'Left club' : (d.error || 'Failed to leave'));
    loadAll();
  };

  const totalConfirmedRegs = events.reduce((s, e) => s + Number(e.confirmed_count || 0), 0);
  const totalWaitlistedRegs = events.reduce((s, e) => s + Number(e.waitlist_count || 0), 0);
  const annCatCount = { general: 0, club: 0, event: 0 };
  announcements.forEach(a => { annCatCount[a.category] = (annCatCount[a.category] || 0) + 1; });
  const pendingClubs = clubs.filter(c => !c.is_approved).length;

  if (loading) return <div className="home-content-loading"><p>Loading admin panel...</p></div>;

  return (
    <>
      <header className="home-welcome">
        <h1>Admin Panel</h1>
        <p>Manage events, clubs, and announcements.</p>
      </header>
      {msg && <span className="field-message form-success">{msg}</span>}

      <div className="admin-stats">
        <div className="admin-stat-card"><strong>{events.length}</strong><span>Events</span></div>
        <div className="admin-stat-card"><strong>{clubs.length}</strong><span>Clubs{pendingClubs > 0 ? ` (${pendingClubs} pending)` : ''}</span></div>
        <div className="admin-stat-card"><strong>{announcements.length}</strong><span>Announcements</span></div>
        <div className="admin-stat-card"><strong>{totalConfirmedRegs}</strong><span>Confirmed</span></div>
        <div className="admin-stat-card"><strong>{totalWaitlistedRegs}</strong><span>Waitlisted</span></div>
      </div>

      <div className="admin-breakdown">
        <div className="admin-breakdown-item">
          <span className="admin-breakdown-label">Announcements</span>
          <div className="admin-bar-group">
            {annCatCount.general > 0 && <div className="admin-bar admin-bar-general" style={{width: `${announcements.length ? (annCatCount.general/announcements.length*100) : 0}%`}}>{annCatCount.general}</div>}
            {annCatCount.club > 0 && <div className="admin-bar admin-bar-club" style={{width: `${announcements.length ? (annCatCount.club/announcements.length*100) : 0}%`}}>{annCatCount.club}</div>}
            {annCatCount.event > 0 && <div className="admin-bar admin-bar-event" style={{width: `${announcements.length ? (annCatCount.event/announcements.length*100) : 0}%`}}>{annCatCount.event}</div>}
          </div>
          <div className="admin-bar-legend">
            <span><span className="legend-dot legend-general"></span>School ({annCatCount.general})</span>
            <span><span className="legend-dot legend-club"></span>Club ({annCatCount.club})</span>
            <span><span className="legend-dot legend-event"></span>Event ({annCatCount.event})</span>
          </div>
        </div>
      </div>

      <div className="home-grid">

        {/* Events */}
        <div className="home-card">
          <div className="home-card-header"><h2>Create Event</h2></div>
          <div className="home-card-body">
            <form className="admin-form" onSubmit={handleCreateEvent}>
              <input type="text" placeholder="Title" value={newEvent.title} onChange={e => setNewEvent({...newEvent, title: e.target.value})} required />
              <textarea placeholder="Description" value={newEvent.description} onChange={e => setNewEvent({...newEvent, description: e.target.value})} />
              <input type="datetime-local" value={newEvent.event_date} onChange={e => setNewEvent({...newEvent, event_date: e.target.value})} required />
              <input type="text" placeholder="Location" value={newEvent.location} onChange={e => setNewEvent({...newEvent, location: e.target.value})} />
              <input type="number" min="1" placeholder="Max capacity" value={newEvent.max_capacity} onChange={e => setNewEvent({...newEvent, max_capacity: e.target.value})} required />
              <button type="submit" className="primary-action">Create Event</button>
            </form>
          </div>
        </div>

        <div className="home-card" style={{gridColumn: '1 / -1'}}>
          <div className="home-card-header"><h2>Events</h2></div>
          <div className="home-card-body">
            {events.length === 0 ? <p className="home-empty">No events.</p> : events.map(ev => (
              <div key={ev.event_id} className="admin-list-item">
                {editingEvent?.event_id === ev.event_id ? (
                  <div className="admin-inline-edit">
                    <input value={editingEvent.title} onChange={e => setEditingEvent({...editingEvent, title: e.target.value})} />
                    <textarea value={editingEvent.description} onChange={e => setEditingEvent({...editingEvent, description: e.target.value})} />
                    <input type="datetime-local" value={editingEvent.event_date} onChange={e => setEditingEvent({...editingEvent, event_date: e.target.value})} />
                    <div className="profile-edit-actions">
                      <button className="primary-action" onClick={() => handleUpdateEvent(ev.event_id)}>Save</button>
                      <button className="home-card-more" onClick={() => setEditingEvent(null)}>Cancel</button>
                    </div>
                  </div>
                ) : (
                  <div className="admin-list-row">
                    <div>
                      <strong>{ev.title}</strong>
                      <small>{ev.event_date?.slice(0, 10)}</small>
                      <div className="admin-cap-bar">
                        <div className="admin-cap-fill" style={{width: `${ev.max_capacity ? (Number(ev.confirmed_count)/Number(ev.max_capacity)*100) : 0}%`}}></div>
                        <span className="admin-cap-label">{ev.confirmed_count || 0}/{ev.max_capacity}</span>
                      </div>
                    </div>
                    <div className="admin-list-actions">
                      <button className="home-card-more" onClick={() => setEditingEvent(ev)}>Edit</button>
                      <button className="danger-action-sm" onClick={() => handleDeleteEvent(ev.event_id)}>Delete</button>
                    </div>
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>

        {/* Clubs */}
        <div className="home-card">
          <div className="home-card-header"><h2>Create Club</h2></div>
          <div className="home-card-body">
            <form className="admin-form" onSubmit={handleCreateClub}>
              <input type="text" placeholder="Club name" value={newClub.club_name} onChange={e => setNewClub({...newClub, club_name: e.target.value})} required />
              <textarea placeholder="Description" value={newClub.description} onChange={e => setNewClub({...newClub, description: e.target.value})} />
              <button type="submit" className="primary-action">Create Club</button>
            </form>
          </div>
        </div>

        <div className="home-card" style={{gridColumn: '1 / -1'}}>
          <div className="home-card-header"><h2>Clubs</h2></div>
          <div className="home-card-body">
            {clubs.length === 0 ? <p className="home-empty">No clubs.</p> : clubs.map(c => (
              <div key={c.club_id} className="admin-list-item">
                {editingClub?.club_id === c.club_id ? (
                  <div className="admin-inline-edit">
                    <input value={editingClub.club_name} onChange={e => setEditingClub({...editingClub, club_name: e.target.value})} />
                    <textarea value={editingClub.description} onChange={e => setEditingClub({...editingClub, description: e.target.value})} />
                    <div className="profile-edit-actions">
                      <button className="primary-action" onClick={() => handleUpdateClub(c.club_id)}>Save</button>
                      <button className="home-card-more" onClick={() => setEditingClub(null)}>Cancel</button>
                    </div>
                  </div>
                ) : (
                  <div className="admin-list-row">
                    <div>
                      <strong>{c.club_name}</strong>
                      <div className="admin-mem-bar">
                        <div className="admin-mem-fill" style={{width: `${Math.min(Number(c.member_count || 0) * 10, 100)}%`}}></div>
                        <span className="admin-mem-label">{c.member_count || 0} member{(c.member_count||0) !== 1 ? 's' : ''}{!c.is_approved ? ' — Pending' : ''}</span>
                      </div>
                    </div>
                    <div className="admin-list-actions">
                      <button className="home-card-more" onClick={() => handleViewClubMembers(c)}>Members</button>
                      <button className="home-card-more" style={{color:'#e74c3c'}} onClick={() => handleLeaveClub(c.club_id)}>Leave</button>
                      <button className="home-card-more" onClick={() => setEditingClub(c)}>Edit</button>
                      <button className="danger-action-sm" onClick={() => handleDeleteClub(c.club_id)}>Delete</button>
                    </div>
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>

        {/* Assign Leader */}
        <div className="home-card">
          <div className="home-card-header"><h2>Assign Club Leader</h2></div>
          <div className="home-card-body">
            <div className="admin-form">
              <label>Select Club
                <select value={selectedClub} onChange={e => { setSelectedClub(e.target.value); loadClubMembers(e.target.value); }}>
                  <option value="">-- Choose --</option>
                  {clubs.map(c => <option key={c.club_id} value={c.club_id}>{c.club_name}</option>)}
                </select>
              </label>
              {clubMembers.length > 0 && (
                <div className="club-member-list">
                  <p>Members:</p>
                  {clubMembers.map(m => (
                    <div key={m.user_id} className="profile-row">
                      <span>{m.display_name} ({m.role})</span>
                      {m.role !== 'leader' && <button className="home-card-more" onClick={() => handleAssignLeader(m.user_id)}>Make Leader</button>}
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Announcements */}
        <div className="home-card">
          <div className="home-card-header"><h2>Create Announcement</h2></div>
          <div className="home-card-body">
            <form className="admin-form" onSubmit={handleCreateAnnouncement}>
              <input type="text" placeholder="Title" value={newAnnouncement.title} onChange={e => setNewAnnouncement({...newAnnouncement, title: e.target.value})} required />
              <textarea placeholder="Body" value={newAnnouncement.body} onChange={e => setNewAnnouncement({...newAnnouncement, body: e.target.value})} required />
              <select value={newAnnouncement.category} onChange={e => setNewAnnouncement({...newAnnouncement, category: e.target.value, club_id: '', event_id: ''})}>
                <option value="general">General</option>
                <option value="club">Club</option>
                <option value="event">Event</option>
              </select>
              {newAnnouncement.category === 'club' && (
                <select value={newAnnouncement.club_id} onChange={e => setNewAnnouncement({...newAnnouncement, club_id: e.target.value})} required>
                  <option value="">-- Select Club --</option>
                  {clubs.map(c => <option key={c.club_id} value={c.club_id}>{c.club_name}</option>)}
                </select>
              )}
              {newAnnouncement.category === 'event' && (
                <select value={newAnnouncement.event_id} onChange={e => setNewAnnouncement({...newAnnouncement, event_id: e.target.value})} required>
                  <option value="">-- Select Event --</option>
                  {events.map(e => <option key={e.event_id} value={e.event_id}>{e.title}</option>)}
                </select>
              )}
              <button type="submit" className="primary-action">Create Announcement</button>
            </form>
          </div>
        </div>

        <div className="home-card" style={{gridColumn: '1 / -1'}}>
          <div className="home-card-header"><h2>Announcements</h2></div>
          <div className="home-card-body">
            {announcements.length === 0 ? <p className="home-empty">No announcements.</p> : announcements.map(a => (
              <div key={a.announcement_id} className="admin-list-item">
                {editingAnnouncement?.announcement_id === a.announcement_id ? (
                  <div className="admin-inline-edit">
                    <input value={editingAnnouncement.title} onChange={e => setEditingAnnouncement({...editingAnnouncement, title: e.target.value})} />
                    <textarea value={editingAnnouncement.body} onChange={e => setEditingAnnouncement({...editingAnnouncement, body: e.target.value})} />
                    <div className="profile-edit-actions">
                      <button className="primary-action" onClick={() => handleUpdateAnnouncement(a.announcement_id)}>Save</button>
                      <button className="home-card-more" onClick={() => setEditingAnnouncement(null)}>Cancel</button>
                    </div>
                  </div>
                ) : (
                  <div className="admin-list-row">
                    <div>
                      <strong>{a.title}</strong>
                      <small>{a.body?.slice(0, 60)}...</small>
                    </div>
                    <div className="admin-list-actions">
                      <button className="home-card-more" onClick={() => setEditingAnnouncement(a)}>Edit</button>
                      <button className="danger-action-sm" onClick={() => handleDeleteAnnouncement(a.announcement_id)}>Delete</button>
                    </div>
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>

      </div>

      {viewClubMembers && (
        <div className="modal-overlay" onClick={() => { setViewClubMembers(null); setViewClubMembersData(null); }}>
          <div className="modal-content" onClick={e => e.stopPropagation()}>
            <h2>{viewClubMembers.club_name} — Members</h2>
            <button className="modal-close" onClick={() => { setViewClubMembers(null); setViewClubMembersData(null); }}>×</button>
            {!viewClubMembersData ? (
              <p className="home-empty">Loading...</p>
            ) : viewClubMembersData.length === 0 ? (
              <p className="home-empty">No members.</p>
            ) : (
              <table className="members-table">
                <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th></tr></thead>
                <tbody>
                  {viewClubMembersData.map((m, i) => (
                    <tr key={m.user_id}><td>{i+1}</td><td>{m.display_name}</td><td>{m.user_email}</td><td>{m.role}</td></tr>
                  ))}
                </tbody>
              </table>
            )}
          </div>
        </div>
      )}
    </>
  );
}
