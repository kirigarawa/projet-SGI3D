/**
 * SGI3D - Moteur de base de données localStorage
 * v3.0 - Compatible export USB
 */

const SGI3D_DB = {
  VERSION: '3.0',

  // ─── Initialisation ──────────────────────────────────────────────────────
  init() {
    if (!localStorage.getItem('sgi3d_initialized')) {
      this._createDefaultData();
      localStorage.setItem('sgi3d_initialized', 'true');
    }
  },

  _createDefaultData() {
    // Utilisateurs par défaut
    const users = [
      { id: 1, email: 'admin@sgi3d.fr',    password: 'admin123', name: 'Administrateur', role: 'admin',    avatar: 'AD', active: true,  createdAt: new Date().toISOString() },
      { id: 2, email: 'marie@sgi3d.fr',    password: 'marie456', name: 'Marie Dupont',   role: 'operator', avatar: 'MD', active: true,  createdAt: new Date().toISOString() },
      { id: 3, email: 'thomas@sgi3d.fr',   password: 'thomas789',name: 'Thomas Martin',  role: 'operator', avatar: 'TM', active: true,  createdAt: new Date().toISOString() }
    ];
    this.set('users', users);

    // Journaux de connexion vides
    this.set('login_logs', []);

    // Travaux d'impression vides
    this.set('print_jobs', []);

    // Caméras par défaut
    const cameras = [
      { id: 1, name: 'Caméra Atelier 1',   location: 'Zone Ultimaker',   status: 'online',  url: '', motionDetect: true,  addedAt: new Date().toISOString() },
      { id: 2, name: 'Caméra Atelier 2',   location: 'Zone Geeetech',    status: 'online',  url: '', motionDetect: true,  addedAt: new Date().toISOString() },
      { id: 3, name: 'Caméra Entrée',       location: 'Hall d\'entrée',   status: 'online',  url: '', motionDetect: false, addedAt: new Date().toISOString() },
      { id: 4, name: 'Caméra Stockage',     location: 'Zone matériaux',   status: 'offline', url: '', motionDetect: true,  addedAt: new Date().toISOString() }
    ];
    this.set('cameras', cameras);

    // Alertes par défaut
    const alerts = [
      { id: 1, type: 'warning', title: 'Filament faible',         message: 'Ultimaker 2+ : niveau filament PLA < 10%',         source: 'Système',        resolved: false, createdAt: new Date(Date.now()-3600000).toISOString(), resolvedAt: null },
      { id: 2, type: 'info',    title: 'Maintenance planifiée',   message: 'Entretien préventif Geeetech A20T prévu demain',    source: 'Admin',          resolved: false, createdAt: new Date(Date.now()-7200000).toISOString(), resolvedAt: null },
      { id: 3, type: 'error',   title: 'Erreur température',      message: 'Buse Ultimaker 2+ température anormale détectée',  source: 'Capteur',        resolved: true,  createdAt: new Date(Date.now()-86400000).toISOString(), resolvedAt: new Date(Date.now()-3000000).toISOString() }
    ];
    this.set('alerts', alerts);

    // Paramètres système
    this.set('settings', { siteName: 'SGI3D', version: '3.0', exportDate: null });
  },

  // ─── CRUD générique ───────────────────────────────────────────────────────
  get(key)        { try { return JSON.parse(localStorage.getItem('sgi3d_' + key)) || []; } catch { return []; } },
  set(key, value) { localStorage.setItem('sgi3d_' + key, JSON.stringify(value)); },
  getObj(key)     { try { return JSON.parse(localStorage.getItem('sgi3d_' + key)) || {}; } catch { return {}; } },

  // ─── Utilisateurs ─────────────────────────────────────────────────────────
  getUsers()      { return this.get('users'); },
  getUserById(id) { return this.getUsers().find(u => u.id === id); },
  getUserByEmail(email) { return this.getUsers().find(u => u.email === email); },

  createUser(data) {
    const users = this.getUsers();
    const newUser = { ...data, id: Date.now(), createdAt: new Date().toISOString(), active: true };
    users.push(newUser);
    this.set('users', users);
    return newUser;
  },

  updateUser(id, data) {
    const users = this.getUsers().map(u => u.id === id ? { ...u, ...data } : u);
    this.set('users', users);
  },

  deleteUser(id) {
    this.set('users', this.getUsers().filter(u => u.id !== id));
  },

  // ─── Authentification ─────────────────────────────────────────────────────
  login(email, password) {
    const user = this.getUserByEmail(email);
    const success = user && user.password === password && user.active;
    this.logLogin({ userId: user ? user.id : null, email, success, userName: user ? user.name : 'Inconnu' });
    if (success) {
      sessionStorage.setItem('sgi3d_session', JSON.stringify({ userId: user.id, email: user.email, name: user.name, role: user.role, loginAt: new Date().toISOString() }));
    }
    return success ? user : null;
  },

  logout() {
    const session = this.getSession();
    if (session) this.logLogin({ userId: session.userId, email: session.email, success: 'logout', userName: session.name });
    sessionStorage.removeItem('sgi3d_session');
  },

  getSession() { try { return JSON.parse(sessionStorage.getItem('sgi3d_session')); } catch { return null; } },
  isLoggedIn()  { return !!this.getSession(); },
  isAdmin()     { const s = this.getSession(); return s && s.role === 'admin'; },

  requireAuth(redirectUrl) {
    if (!this.isLoggedIn()) { window.location.href = redirectUrl || 'login.html'; return false; }
    return true;
  },

  // ─── Journaux de connexion ────────────────────────────────────────────────
  logLogin(data) {
    const logs = this.get('login_logs');
    logs.unshift({ id: Date.now(), ...data, ip: '192.168.1.' + Math.floor(Math.random()*50+10), browser: navigator.userAgent.split(' ').pop(), timestamp: new Date().toISOString() });
    if (logs.length > 500) logs.splice(500);
    this.set('login_logs', logs);
  },

  getLoginLogs(limit) {
    const logs = this.get('login_logs');
    return limit ? logs.slice(0, limit) : logs;
  },

  // ─── Travaux d'impression ─────────────────────────────────────────────────
  getPrintJobs(limit) {
    const jobs = this.get('print_jobs');
    return limit ? jobs.slice(0, limit) : jobs;
  },

  createPrintJob(data) {
    const session = this.getSession();
    const jobs = this.get('print_jobs');
    const job = {
      id: Date.now(),
      userId: session ? session.userId : null,
      userName: session ? session.name : 'Inconnu',
      ...data,
      status: 'en_cours',
      startedAt: new Date().toISOString(),
      finishedAt: null
    };
    jobs.unshift(job);
    this.set('print_jobs', jobs);
    return job;
  },

  updatePrintJob(id, data) {
    const jobs = this.getPrintJobs().map(j => j.id === id ? { ...j, ...data } : j);
    this.set('print_jobs', jobs);
  },

  finishPrintJob(id, success) {
    this.updatePrintJob(id, { status: success ? 'terminé' : 'erreur', finishedAt: new Date().toISOString() });
  },

  // ─── Caméras ──────────────────────────────────────────────────────────────
  getCameras()  { return this.get('cameras'); },

  addCamera(data) {
    const cameras = this.getCameras();
    const cam = { ...data, id: Date.now(), status: 'online', addedAt: new Date().toISOString() };
    cameras.push(cam);
    this.set('cameras', cameras);
    return cam;
  },

  updateCamera(id, data) {
    this.set('cameras', this.getCameras().map(c => c.id === id ? { ...c, ...data } : c));
  },

  deleteCamera(id) {
    this.set('cameras', this.getCameras().filter(c => c.id !== id));
  },

  // ─── Alertes ──────────────────────────────────────────────────────────────
  getAlerts(resolved) {
    const alerts = this.get('alerts');
    if (resolved === undefined) return alerts;
    return alerts.filter(a => a.resolved === resolved);
  },

  addAlert(data) {
    const alerts = this.get('alerts');
    const alert = { ...data, id: Date.now(), resolved: false, createdAt: new Date().toISOString(), resolvedAt: null };
    alerts.unshift(alert);
    this.set('alerts', alerts);
    return alert;
  },

  resolveAlert(id) {
    this.set('alerts', this.get('alerts').map(a => a.id === id ? { ...a, resolved: true, resolvedAt: new Date().toISOString() } : a));
  },

  deleteAlert(id) {
    this.set('alerts', this.get('alerts').filter(a => a.id !== id));
  },

  // ─── Statistiques ─────────────────────────────────────────────────────────
  getStats() {
    return {
      totalUsers:     this.getUsers().length,
      activeUsers:    this.getUsers().filter(u => u.active).length,
      totalLogins:    this.getLoginLogs().length,
      successLogins:  this.getLoginLogs().filter(l => l.success === true).length,
      totalPrintJobs: this.getPrintJobs().length,
      activePrintJobs:this.getPrintJobs().filter(j => j.status === 'en_cours').length,
      totalCameras:   this.getCameras().length,
      onlineCameras:  this.getCameras().filter(c => c.status === 'online').length,
      totalAlerts:    this.getAlerts().length,
      unresolvedAlerts:this.getAlerts(false).length
    };
  },

  // ─── Export / Import USB ──────────────────────────────────────────────────
  exportJSON() {
    const data = {
      version: this.VERSION,
      exportDate: new Date().toISOString(),
      users:      this.getUsers(),
      login_logs: this.getLoginLogs(),
      print_jobs: this.getPrintJobs(),
      cameras:    this.getCameras(),
      alerts:     this.getAlerts(),
      settings:   this.getObj('settings')
    };
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'sgi3d_backup_' + new Date().toISOString().slice(0,10) + '.json';
    a.click();
  },

  importJSON(jsonStr) {
    try {
      const data = JSON.parse(jsonStr);
      if (data.users)      this.set('users',      data.users);
      if (data.login_logs) this.set('login_logs',  data.login_logs);
      if (data.print_jobs) this.set('print_jobs',  data.print_jobs);
      if (data.cameras)    this.set('cameras',     data.cameras);
      if (data.alerts)     this.set('alerts',      data.alerts);
      if (data.settings)   this.set('settings',    data.settings);
      return true;
    } catch(e) { console.error('Import error:', e); return false; }
  },

  exportCSV(tableName) {
    const data = this.get(tableName);
    if (!data.length) return;
    const headers = Object.keys(data[0]);
    const rows = data.map(r => headers.map(h => JSON.stringify(r[h] ?? '')).join(','));
    const csv = [headers.join(','), ...rows].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'sgi3d_' + tableName + '_' + new Date().toISOString().slice(0,10) + '.csv';
    a.click();
  },

  // ─── Helpers UI ───────────────────────────────────────────────────────────
  formatDate(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleDateString('fr-FR') + ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  },

  timeAgo(iso) {
    if (!iso) return '—';
    const diff = Date.now() - new Date(iso).getTime();
    if (diff < 60000)   return 'À l\'instant';
    if (diff < 3600000) return Math.floor(diff/60000) + ' min';
    if (diff < 86400000)return Math.floor(diff/3600000) + 'h';
    return Math.floor(diff/86400000) + 'j';
  }
};

// Auto-initialisation
document.addEventListener('DOMContentLoaded', () => SGI3D_DB.init());
