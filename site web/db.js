/**
 * SGI3D - Moteur API v4.0
 *
 * Objet global unique (SGI3D_DB) qui centralise TOUS les accès aux données.
 * Chaque méthode envoie une requête POST fetch vers api.php avec un paramètre
 * "action" qui détermine l'opération à effectuer côté serveur.
 *
 * Ancienne version : les données étaient stockées dans localStorage (navigateur).
 * Version actuelle : toutes les données transitent par api.php → base MySQL.
 *
 * Structure des réponses de l'API :
 *   { ok: true,  data: <résultat> }  → succès
 *   { ok: false, error: <message> }  → erreur
 */

const SGI3D_DB = {

  // URL du point d'entrée de l'API (relatif à la page courante)
  API: 'api.php',

  // ════════════════════════════════════════════════════════
  //  MÉTHODE CENTRALE D'APPEL API
  //  Toutes les méthodes publiques passent par _call().
  //  Elle construit la requête POST, parse le JSON de réponse,
  //  lève une erreur si l'API renvoie ok:false, et retourne data.
  // ════════════════════════════════════════════════════════
  async _call(action, params = {}) {
    try {
      const res = await fetch(this.API, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        // Corps JSON : l'action + tous les paramètres fusionnés
        body:    JSON.stringify({ action, ...params })
      });
      const json = await res.json();
      // Si l'API signale une erreur, on la propage comme exception JS
      if (!json.ok) throw new Error(json.error || 'Erreur API');
      return json.data;
    } catch (e) {
      // Log dans la console pour faciliter le débogage
      console.error('[SGI3D API]', action, e.message);
      throw e;
    }
  },

  // ════════════════════════════════════════════════════════
  //  GESTION DE SESSION
  //  La session est stockée dans sessionStorage (mémoire du
  //  navigateur, effacée à la fermeture de l'onglet).
  //  Elle contient : userId, email, name, role, loginAt.
  // ════════════════════════════════════════════════════════

  // Retourne l'objet session ou null si l'utilisateur n'est pas connecté
  getSession() {
    try { return JSON.parse(sessionStorage.getItem('sgi3d_session')); } catch { return null; }
  },

  // Retourne true si une session est active (utilisateur connecté)
  isLoggedIn() { return !!this.getSession(); },

  // Retourne true si l'utilisateur connecté a le rôle "admin"
  isAdmin()    { const s = this.getSession(); return s && s.role === 'admin'; },

  // Vérifie qu'une session est active.
  // Si non, redirige vers login.html (ou l'URL fournie) et retourne false.
  // Utilisé en haut de chaque page protégée : if(!SGI3D_DB.requireAuth()){}
  requireAuth(redirect) {
    if (!this.isLoggedIn()) {
      window.location.href = redirect || 'login.html';
      return false;
    }
    return true;
  },

  // ════════════════════════════════════════════════════════
  //  AUTHENTIFICATION
  // ════════════════════════════════════════════════════════

  // Tente de connecter l'utilisateur avec email + mot de passe.
  // En cas de succès : stocke la session dans sessionStorage et retourne l'objet user.
  // En cas d'échec  : retourne null.
  // L'API journalise chaque tentative (réussie ou non) dans journaux_connexion.
  async login(email, password) {
    const res = await this._call('login', { email, mot_de_passe: password });
    if (res.success) {
      // Sauvegarde les infos essentielles de la session (sans le mot de passe)
      sessionStorage.setItem('sgi3d_session', JSON.stringify({
        userId:  res.user.id,
        email:   res.user.email,
        name:    res.user.nom,
        role:    res.user.role,
        loginAt: new Date().toISOString()  // Heure de connexion côté client
      }));
      return res.user;
    }
    return null;
  },

  // Déconnecte l'utilisateur :
  // 1. Envoie un événement "déconnexion" à l'API pour journalisation
  // 2. Supprime la session du sessionStorage
  async logout() {
    const s = this.getSession();
    if (s) {
      await this._call('logout', { userId: s.userId, email: s.email, nom: s.name });
    }
    sessionStorage.removeItem('sgi3d_session');
  },

  // ════════════════════════════════════════════════════════
  //  UTILISATEURS
  //  CRUD complet sur la table `utilisateurs`.
  // ════════════════════════════════════════════════════════

  // Retourne tous les utilisateurs (tableau)
  async getUsers()          { return this._call('getUsers'); },

  // Retourne un utilisateur par son id (objet ou null)
  async getUserById(id)     { return this._call('getUserById',    { id }); },

  // Retourne un utilisateur par son email (objet ou null)
  // Utilisé pour vérifier l'unicité d'un email avant création
  async getUserByEmail(e)   { return this._call('getUserByEmail', { email: e }); },

  // Crée un nouvel utilisateur.
  // Accepte les deux conventions de nommage (anglais : name/password, français : nom/mot_de_passe)
  // pour la compatibilité avec les différents formulaires du projet.
  async createUser(data) {
    return this._call('createUser', {
      nom:          data.name  || data.nom,
      email:        data.email,
      mot_de_passe: data.password || data.mot_de_passe,
      role:         data.role  || 'operateur',
      avatar:       data.avatar || ''
    });
  },

  // Met à jour un ou plusieurs champs d'un utilisateur.
  // Les champs autorisés sont filtrés côté api.php (liste blanche).
  async updateUser(id, data) {
    return this._call('updateUser', { id, ...data });
  },

  // Supprime définitivement un utilisateur par son id
  async deleteUser(id) { return this._call('deleteUser', { id }); },

  // ════════════════════════════════════════════════════════
  //  JOURNAUX DE CONNEXION
  //  Historique de toutes les tentatives de connexion/déconnexion.
  // ════════════════════════════════════════════════════════

  // Retourne les N dernières entrées du journal (défaut : 500)
  async getLoginLogs(limit) { return this._call('getLoginLogs', { limit: limit || 500 }); },

  // ════════════════════════════════════════════════════════
  //  TRAVAUX D'IMPRESSION
  //  Suivi du cycle de vie des impressions 3D.
  // ════════════════════════════════════════════════════════

  // Retourne les N derniers travaux d'impression (défaut : 500)
  async getPrintJobs(limit) { return this._call('getPrintJobs', { limit: limit || 500 }); },

  // Crée un nouveau travail d'impression avec le statut "en_cours".
  // Attache automatiquement l'utilisateur connecté (userId + name) depuis la session.
  // Accepte les deux conventions de nommage (anglais/français).
  async createPrintJob(data) {
    const s = this.getSession();
    return this._call('createPrintJob', {
      nom_fichier:     data.fileName     || data.nom_fichier,
      materiau:        data.material     || data.materiau,
      duree_estimee:   data.duration     || data.duree_estimee,
      utilisateur_id:  s ? s.userId : null,     // null si non connecté
      nom_utilisateur: s ? s.name   : 'Inconnu',
      imprimante_id:   data.imprimante_id || null
    });
  },

  // Clôture un travail d'impression.
  // success=true → statut "termine" | success=false → statut "erreur"
  // L'API calcule automatiquement la durée réelle (TIMESTAMPDIFF).
  async finishPrintJob(id, success) {
    return this._call('finishPrintJob', { id, success });
  },

  // ════════════════════════════════════════════════════════
  //  CAMÉRAS
  //  CRUD complet sur la table `cameras`.
  // ════════════════════════════════════════════════════════

  // Retourne toutes les caméras enregistrées (tableau)
  async getCameras() { return this._call('getCameras'); },

  // Ajoute une nouvelle caméra.
  // La détection de mouvement est activée (1) par défaut si non précisée.
  // Accepte les deux conventions de nommage (anglais/français).
  async addCamera(data) {
    return this._call('addCamera', {
      nom:           data.name         || data.nom,
      localisation:  data.location     || data.localisation,
      url_flux:      data.url          || data.url_flux       || null,
      detection_mvt: data.motionDetect !== undefined ? (data.motionDetect ? 1 : 0) : 1
    });
  },

  // Met à jour une ou plusieurs propriétés d'une caméra.
  // Gère la traduction du statut anglais → français :
  //   'online' → 'en_ligne' | 'offline' → 'hors_ligne'
  // Accepte aussi directement la valeur française (statut).
  async updateCamera(id, data) {
    const mapped = { id };
    // Traduction du statut anglais vers la valeur attendue par l'API
    if (data.status       !== undefined) mapped.statut        = data.status === 'online' ? 'en_ligne' : 'hors_ligne';
    if (data.statut       !== undefined) mapped.statut        = data.statut;
    // Conversion booléen → 0/1 pour la détection de mouvement
    if (data.motionDetect !== undefined) mapped.detection_mvt = data.motionDetect ? 1 : 0;
    if (data.detection_mvt !== undefined) mapped.detection_mvt = data.detection_mvt;
    return this._call('updateCamera', mapped);
  },

  // Supprime définitivement une caméra par son id
  async deleteCamera(id) { return this._call('deleteCamera', { id }); },

  // ════════════════════════════════════════════════════════
  //  ALERTES
  //  Gestion complète des notifications système.
  // ════════════════════════════════════════════════════════

  // Retourne les alertes selon leur état :
  //   resolved=true  → alertes résolues uniquement
  //   resolved=false → alertes actives uniquement
  //   resolved=undefined → toutes les alertes
  async getAlerts(resolved) {
    const params = {};
    if (resolved !== undefined) params.resolved = resolved ? '1' : '0';
    return this._call('getAlerts', params);
  },

  // Crée une nouvelle alerte (non résolue par défaut).
  // Accepte les deux conventions de nommage (title/titre).
  async addAlert(data) {
    return this._call('addAlert', {
      type:    data.type,
      titre:   data.title   || data.titre,
      message: data.message,
      source:  data.source  || null
    });
  },

  // Marque une alerte spécifique comme résolue (resolue=1, resolue_le=NOW())
  async resolveAlert(id)   { return this._call('resolveAlert',        { id }); },

  // Supprime définitivement une alerte par son id
  async deleteAlert(id)    { return this._call('deleteAlert',         { id }); },

  // Résout toutes les alertes actives en une seule opération
  async resolveAllAlerts() { return this._call('resolveAllAlerts'); },

  // Supprime toutes les alertes déjà résolues (nettoyage de l'historique)
  async deleteResolvedAlerts() { return this._call('deleteResolvedAlerts'); },

  // ════════════════════════════════════════════════════════
  //  STATISTIQUES
  //  Agrégats globaux pour le dashboard.
  //  Traduit les clés snake_case de l'API en camelCase JS
  //  pour une utilisation plus naturelle dans les templates.
  // ════════════════════════════════════════════════════════
  async getStats() {
    const d = await this._call('getStats');
    return {
      totalUsers:       parseInt(d.total_utilisateurs),   // Nb total de comptes
      activeUsers:      parseInt(d.utilisateurs_actifs),  // Comptes actifs (actif=1)
      successLogins:    parseInt(d.connexions_reussies),  // Connexions réussies
      totalPrintJobs:   parseInt(d.total_print_jobs),     // Tous les travaux d'impression
      activePrintJobs:  parseInt(d.impressions_en_cours), // Travaux "en_cours"
      totalCameras:     parseInt(d.total_cameras),        // Toutes les caméras
      onlineCameras:    parseInt(d.cameras_en_ligne),     // Caméras "en_ligne"
      totalAlerts:      parseInt(d.total_alertes),        // Toutes les alertes
      unresolvedAlerts: parseInt(d.alertes_actives)       // Alertes non résolues
    };
  },

  // ════════════════════════════════════════════════════════
  //  EXPORT JSON
  //  Télécharge un fichier JSON contenant l'intégralité des
  //  données (utilisateurs, journaux, impressions, caméras,
  //  alertes) pour sauvegarde ou transfert sur clé USB.
  //  Le nom du fichier inclut la date du jour (AAAA-MM-JJ).
  // ════════════════════════════════════════════════════════
  async exportJSON() {
    const data = await this._call('exportJSON');
    // Crée un Blob JSON formaté (indenté de 2 espaces pour lisibilité)
    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    // Crée un lien <a> temporaire pour déclencher le téléchargement
    const a    = document.createElement('a');
    a.href     = URL.createObjectURL(blob);
    a.download = 'sgi3d_backup_' + new Date().toISOString().slice(0, 10) + '.json';
    a.click();
    // Note : URL.revokeObjectURL() pourrait être appelé ici pour libérer la mémoire
  },

  // ════════════════════════════════════════════════════════
  //  HELPERS UTILITAIRES
  //  Fonctions de formatage partagées par toutes les pages.
  // ════════════════════════════════════════════════════════

  // Formate une date ISO en "JJ/MM/AAAA HH:MM" selon la locale française.
  // Retourne "—" si la valeur est absente ou invalide.
  formatDate(iso) {
    if (!iso) return '—';
    const d = new Date(iso);
    return d.toLocaleDateString('fr-FR') + ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
  },

  // Retourne le temps écoulé depuis une date ISO sous forme lisible :
  //   < 1 min  → "A l'instant"
  //   < 1 h    → "X min"
  //   < 1 jour → "Xh"
  //   sinon    → "Xj"
  // Utilisé dans les flux d'activité (connexions, alertes, impressions).
  timeAgo(iso) {
    if (!iso) return '—';
    const diff = Date.now() - new Date(iso).getTime();
    if (diff < 60000)    return 'A l\'instant';
    if (diff < 3600000)  return Math.floor(diff / 60000)   + ' min';
    if (diff < 86400000) return Math.floor(diff / 3600000) + 'h';
    return Math.floor(diff / 86400000) + 'j';
  },

  // Méthode vide conservée pour la rétrocompatibilité.
  // Dans l'ancienne version (localStorage), init() chargeait les données initiales.
  // Aujourd'hui les données viennent de l'API, donc init() ne fait plus rien.
  init() {}
};

// Déclenchement automatique d'init() au chargement du DOM.
// No-op (ne fait rien), mais préserve la compatibilité avec
// les pages qui s'attendaient à ce comportement.
document.addEventListener('DOMContentLoaded', () => SGI3D_DB.init());
