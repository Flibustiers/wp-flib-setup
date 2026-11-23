# Changelog

## 2.0.0 — 23/11/2026

Passage d'une extension à un **fichier unique** (`flib-setup.php`) à déposer à la racine du site depuis le gestionnaire de fichiers N0C.

Nouveautés
- Connexion administrateur WordPress dans le fichier lui-même (fonctionne même si l'administration n'est pas encore accessible par l'URL temporaire).
- URL temporaire : `WP_HOME` / `WP_SITEURL` écrits dans wp-config.php (bloc à repères, sauvegarde avant modification).
- Champ « Nom du site ».
- Import du favicon et du manifest (zip ou dossier) + mu-plugin `flib-favicon.php`.
- Installation de flib-base depuis GitHub, thème enfant au nom du site, suppression des autres thèmes.
- Installation des extensions maison (GitHub) et WordPress.org ; listes modifiables en haut du fichier.
- Enregistrement de `FLIB_GITHUB_TOKEN` dans wp-config.php.
- Choix des étapes par cases à cocher, compte rendu détaillé.
- Sécurité : validité de 2 heures après dépôt, 5 essais de connexion, suppression automatique.

Corrections par rapport à la 1.0
- La suppression du contenu ne touche plus que le contenu de démonstration non modifié (la 1.0 supprimait toutes les pages, articles, commentaires et médias).
- Indexation bloquée par défaut pendant le développement (la 1.0 l'ouvrait).
- Traductions françaises réellement téléchargées (la 1.0 changeait seulement l'option de langue).
- Règles de réécriture écrites dans .htaccess ; fichier créé si absent.
- Page « Politique de confidentialité » distincte des mentions légales.
- Menu rempli et utilisable par flib-base (menu de navigation en blocs).
- En-têtes de sécurité : HSTS sans `includeSubDomains`, `X-XSS-Protection` (obsolète) retiré.
- Suppression de Hello Dolly et Akismet.

## 1.0.0

Extension « FLIB - WP Setup » (Clément GUEZOU) : réglages de base, création des pages, suppression du contenu, en-têtes de sécurité.
