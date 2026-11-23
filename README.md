# FLIB - Setup WordPress (tout-en-un)

Un seul fichier, `flib-setup.php`, à déposer à la racine d'un site WordPress fraîchement créé sur PlanetHoster (N0C). Il met le site en place puis se supprime.

## Utilisation

1. Créer le site WordPress sur PlanetHoster.
2. Télécharger `flib-setup.php` (dernière [release](../../releases/latest)) et le déposer à la racine du site, à côté de `wp-config.php`, avec le gestionnaire de fichiers N0C.
3. Ouvrir `https://<url-temporaire>/flib-setup.php`.
4. Se connecter avec le compte administrateur WordPress, remplir le formulaire (nom du site, URL temporaire pré-remplie, jeton GitHub, favicon), cocher / décocher les étapes, lancer.
5. Lire (et garder si besoin) le compte rendu : le fichier est déjà supprimé du serveur.
6. Une fois le site validé, supprimer les sauvegardes `wp-config-sauvegarde-flib-*.php`.

Le fichier fonctionne même si l'administration n'est pas encore accessible par l'URL temporaire : il charge WordPress lui-même avec cette URL.

## Ce que fait le setup

| Étape | Détail |
|---|---|
| wp-config.php | `WP_HOME` / `WP_SITEURL` = URL temporaire, `FLIB_GITHUB_TOKEN`. Bloc entre repères `BEGIN/END FLIB WP SETUP`, sauvegarde horodatée avant modification, anciennes définitions commentées. |
| Identité | Nom du site, slogan vidé. |
| Langue | Traductions fr_FR téléchargées, puis celles des extensions et du thème. |
| Réglages | Fuseau Europe/Paris, formats de date, commentaires et pings fermés, 9 articles par page, inscriptions fermées, permaliens `/%postname%/`, indexation bloquée pendant le développement. |
| Contenu par défaut | « Bonjour tout le monde », page d'exemple, brouillon de confidentialité, commentaire de démo : supprimés **seulement s'ils n'ont jamais été modifiés**. Le reste du site n'est jamais touché. |
| Pages | Accueil (page d'accueil statique), Contact, Mentions légales, Politique de confidentialité. |
| Thème | flib-base (dernière release), thème enfant au nom du site copié depuis `theme/flib-enfant`, activation, suppression des autres thèmes. |
| Extensions | Maison (releases GitHub) et WordPress.org, installées et activées. Hello Dolly et Akismet supprimés. |
| Menu | « Menu principal » (Accueil, Contact) pour le bloc Navigation de l'en-tête flib-base. |
| .htaccess | Règles de réécriture WordPress, en-têtes de sécurité (HSTS, nosniff, SAMEORIGIN, Referrer-Policy, Permissions-Policy). |
| Favicon | Zip ou dossier : fichiers copiés à la racine, nom du site reporté dans le manifest, balises ajoutées par le mu-plugin `flib-favicon.php`. |

## Sécurité

- Utilisable seulement dans les **2 heures** qui suivent le dépôt du fichier sur le serveur (date d'arrivée `filectime`), puis il se supprime.
- Connexion obligatoire avec un compte **administrateur** WordPress ; 5 essais au maximum, puis suppression.
- Suppression automatique à la fin du setup.
- Fichiers du favicon filtrés (extensions autorisées, SVG sans script, aucun PHP).

## Modifier les listes d'extensions, pages, menu

Tout est en haut du fichier, dans `flib_setup_config()` :

```php
'wporg' => [
    'mon-extension' => ['name' => 'Mon extension', 'default' => true],   // slug WordPress.org
],
'github' => [
    'flib-truc' => ['name' => 'Flib Truc', 'repo' => 'Flibustiers/wp-flib-truc', 'default' => true],
],
```

`default => false` : proposée mais décochée. Après modification : monter `FLIB_SETUP_VERSION`, compléter le CHANGELOG, committer, puis tag `vX.Y.Z` : la GitHub Action vérifie la syntaxe (PHP 7.4 à 8.4) et publie le fichier dans la release.

## Jeton GitHub

*Fine-grained*, propriétaire Flibustiers, *Only select repositories* : `flib-base` et les dépôts des extensions maison, permission *Contents : Read-only*. C'est le même jeton que celui des mises à jour intégrées (`FLIB_GITHUB_TOKEN`).

## Prérequis

WordPress 6.8+, PHP 7.4+ (8.1+ pour flib-base), écriture directe des fichiers (hébergements N0C).
