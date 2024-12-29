# Rendu sur la machine VulnCMS

## Introduction

Ce document retrace l'exploitation de la machine **VulnCMS**. Cette machine virtuelle, construite autour de CMS populaires, propose divers services web que nous avons analysés pour identifier des failles exploitables. Chaque étape est expliquée avec précision pour maintenir le fil logique de la démarche.

---

## Étape 1 : Reconnaissance

### 1.1 Découverte de la machine

La première étape a consisté à identifier l’adresse IP de la machine cible. Un scan réseau a révélé l’IP **192.168.228.167**, qui servira de base pour les étapes suivantes.

<div align="center">
    <img src="./images/netdiscover.png" alt="Découverte de la machine avec netdiscover">
</div>

### 1.2 Scan des ports ouverts

Un scan Nmap a mis en évidence plusieurs ports ouverts, associés à des services web basés sur des CMS bien connus :

- **Nginx** sur le port **80**
- **WordPress** sur le port **5000**
- **Joomla** sur le port **8001**
- **Drupal** sur le port **9001**

<div align="center">
    <img src="./images/nmap.png" alt="Scan Nmap des ports ouverts">
</div>

---

## Étape 2 : Attaque

### 2.1 Nginx
---

En accédant au site web sur le port 80, la page d’accueil standard de Nginx apparaît. Une exploration des fichiers accessibles a révélé plusieurs pages intéressantes.

Déjà on comprend que le site est basé sur la série `Mr. Robot` avec des références à `fsociety`, `Elliot` et `Mobley`.

<div align="center">
    <img src="./images/nginx/accueil.png" alt="Fichier accueil home.html">
</div>
<div align="center">
    <img src="./images/nginx/home.png" alt="Page home.html">
</div>

Le fichier `/robots.txt` mentionne une page cachée, `/about.html`, mais celle-ci ne contient aucune information exploitable.

<div align="center">
    <img src="./images/nginx/robots.png" alt="Fichier robots.txt">
</div>
<div align="center">
    <img src="./images/nginx/about.png" alt="Fichier about.html">
</div>

On ne retrouve pas d'informations vraiment intéressantes sur ce site, mis à part les références à la série `Mr. Robot`.

Des recherches sur la version `1.14.0` de Nginx n’ont pas permis d’identifier d’exploit applicable. Les investigations se sont donc orientées vers d’autres services.

<div align="center">
    <img src="./images/nginx/noexploit.png" alt="Aucun exploit Nginx">
</div>


<br><br><br>

### 2.2 WordPress
---


Le site WordPress, accessible sur le port **5000**, présentait initialement un problème d’affichage : les fichiers CSS ne se chargeaient pas. Une inspection du code source a révélé que le domaine `fsociety.web` devait être ajouté au fichier `/etc/hosts`.

**Avant :**

<div align="center">
    <img src="./images/wordpress/hostsmissing.png" alt="Problème CSS">
</div>

**Après modification :**

<div align="center">
    <img src="./images/wordpress/hostsadded.png" alt="Site WordPress fonctionnel">
</div>

Machinalement, quand on a un service WordPress, on tente une attaque par force brute sur `/admin`. C'est ce que j'ai fait avec Metasploit.

<div align="center">
    <img src="./images/wordpress/admin_form.png" alt="Page d’administration WordPress">
</div>
<div align="center">
    <img src="./images/wordpress/wp-bruteforce.png" alt="Force brute">
</div>
<div align="center">
    <img src="./images/wordpress/wp-brute-failed.png" alt="Résultat force brute">
</div>

L’attaque par force brute a échoué.

Cependant, une faute de frappe inattendue a révélé un webshell caché à l’adresse `/amdin`.

<div align="center">
    <img src="./images/wordpress/typoWEBSHELL.png" alt="Webshell découvert">
</div>
<div align="center">
    <img src="./images/wordpress/webshell-whoami.png" alt="Webshell whoami">
</div>

Ce webshell, bien que fonctionnel, n'est pas très pratique : il y a des problèmes avec l’échappement des caractères, et les commandes ne sont pas très lisibles. J'ai donc décidé de lancer un reverse shell en utilisant un script bash.

<div align="center">
    <img src="./images/wordpress/reverseShell.png" alt="Reverse shell établi">
</div>

J'ai ensuite pu lancer un LinPEAS (un script d'audit de sécurité) pour analyser la machine et trouver des failles potentielles. Il m'a trouvé pas mal d'informations intéressantes, notamment un fichier contenant un mot de passe pour l'utilisateur `tyrell`.

<div align="center">
    <img src="./images/wordpress/linpeas.png" alt="Analyse de la machine avec LinPEAS">
</div>
<div align="center">
    <img src="./images/wordpress/linpeas-tyrell-pass.png" alt="Fichier passwd trouvé avec LinPEAS">
</div>
<div align="center">
    <img src="./images/wordpress/tyrell-passwd.png" alt="Mot de passe trouvé avec LinPEAS">
</div>
<div align="center">
    <img src="./images/wordpress/tyrell-ssh.png" alt="Connexion en tant que tyrell">
</div>

Dès qu'on a un accès utilisateur, on cherche à obtenir un accès root. Pour cela, on peut utiliser `sudo -l` pour voir les commandes que l'on peut exécuter avec les droits `sudo`. On peut voir que l'on peut exécuter `journalctl` sans mot de passe. Il nous suffit donc de suivre les instructions de [GTFOBins](https://gtfobins.github.io/gtfobins/journalctl/) pour obtenir un accès root.

<div align="center">
    <img src="./images/wordpress/pwnd.png" alt="Accès root obtenu">
</div>

---
<br><br><br>

### 2.3 Joomla
---


Le site Joomla, accessible sur le port **8001**, utilisait la version `3.4.3`. Un scan avec `joomscan` a confirmé une vulnérabilité à une injection SQL.

<div align="center">
    <img src="./images/joomla/joomscan.png" alt="Scan de Joomla avec joomscan">
</div>

Grâce aux informations obtenues, on a pu faire plus de recherches sur cette faille.

<div align="center">
    <img src="./images/joomla/CVE.png" alt="Informations sur l’exploit Joomla">
</div>

L’attaque a été menée avec `sqlmap` pour exploiter cette faille.

<div align="center">
    <img src="./images/joomla/tentativesSQLI.png" alt="Exploitation avec sqlmap">
</div>
<div align="center">
    <img src="./images/joomla/sqlmap-found-db.png" alt="Informations obtenues">
</div>

Dans ce dump, on a pu trouver un mot de passe hashé pour l'utilisateur `elliot`. J'ai remarqué que le champ `mail` contenait probablement un indice. Après analyse, j'ai essayé le mot de passe `5te!_M0un71@N`, et cela a fonctionné.

<div align="center">
    <img src="./images/joomla/connected-elliot.png" alt="Connexion en tant qu’elliot">
</div>

Une fois connecté, les mêmes techniques que pour WordPress ont été appliquées pour obtenir un accès root.

---

<br><br><br>

### 2.4 Drupal
---

Drupal, sur le port **9001**, a été exploité avec l’exploit `drupal_drupalgeddon2` disponible dans Metasploit.

<div align="center">
    <img src="./images/drupal/drupal-metasploit.jpg" alt="Recherche de l’exploit Drupal">
</div>
<div align="center">
    <img src="./images/drupal/drupal-config.png" alt="Configuration de l’exploit Drupal">
</div>
<div align="center">
    <img src="./images/drupal/exploited.png" alt="Accès obtenu avec l’exploit Drupal">
</div>

---

## Conclusion

En conclusion, l'exploitation de la machine **VulnCMS** a permis de mettre en évidence plusieurs failles de sécurité dans des services web basés sur des CMS populaires :

- **Nginx** : aucune faille exploitée (fausse piste avec des références à `Mr. Robot`).
- **WordPress** : exploitation d’un webshell et élévation de privilèges.
- **Joomla** : exploitation d’une injection SQL.
- **Drupal** : exploitation de la faille `Drupalgeddon2`.

Ces attaques ont permis d’obtenir des accès utilisateurs, puis des accès root, en exploitant des failles bien connues. L'exploit le plus intéressant a été celui de Joomla, qui a nécessité des recherches et une exploitation manuelle.
