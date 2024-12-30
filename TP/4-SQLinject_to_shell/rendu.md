# Rendu sur la machine pentest lab SQLinject to shell

## Introduction

Ce document retrace l'exploitation de la machine SQLinject to shell du pentest lab.

---


## Étape 1 : Reconnaissance

### 1.1 Découverte de la machine

Comme d'habitude, nous commençons par scanner le réseau pour découvrir les machines.

<p align="center">
  <img src="images/netdiscover.jpg" alt="netdiscover">
</p>

La machine a pour adresse IP `192.168.228.132`.

### 1.2 Scan des ports ouverts

On va maintenant scanner les ports ouverts sur la machine.
En utilisant Nmap avec l'options `-sV` pour déterminer les versions des services.

<p align="center">
  <img src="images/nmap.jpg" alt="nmap">
</p>

On remarque plusieurs services : 

- Un service `SSH` sur le port 22 (OpenSSH 5.5p1)
- Un service `HTTP` sur le port 80 (Apache httpd 2.2.16)

## Étape 2 : Attaque

### 2.1 Apache

On commence par visiter la page web de la machine.

<p align="center">
  <img src="images/page-web-avec-index.jpg" alt="page-web-avec-index">
</p>

On se retrouve sur des pages web présentant du contenu. On peut voir dans l'url qu'on à des paramètres, Si on clique sur une image on voit dans l'url `?id=*` on peut donc penser à une injection SQL (c'est le thème de la machine).

On a aussi une page admin qui nous demande des identifiants.

<p align="center">
  <img src="images/admin.png" alt="admin">
</p>



Mais ayant fait le TD sur sqlmap juste avant, j'ai directement essayé de lancer une injection SQL sur le paramètre `id` de l'url.

<p align="center">
  <img src="images/tentative-sql-1.jpg" alt="tentative-sql-1">
</p>

<p align="center">
  <img src="images/SQLMAP-REUSSIS.jpg" alt="SQLMAP-REUSSIS">
</p>

Avec sqlmap on a pu récupérer les bases de données disponibles : 
- `photoblog` : Base de données du site
- `information_schema` : Base de données système

On va maintenant essayer de récupérer les tables de la base de données `photoblog`.


<p align="center">
  <img src="images/TABLE-PHOTOBLOG.jpg" alt="TABLE-PHOTOBLOG">
</p>

On sait maintenant qu'on va avoir des données intéressantes dans la base de données, on va donc essayer de la dump.

<p align="center">
  <img src="images/DUMP-photoblog.jpg" alt="DUMP-photoblog">
</p>

<p align="center">
  <img src="images/dump-password-hacked.jpg"
    alt="dump-password-hacked"> 
</p>


Sqlmap à réussi à dump toutes les tables de la base de données `photoblog`. Il a même réussi à récupérer le mot de passe de l'utilisateur `admin` : `P4ssw0rd`.

On va maintenant pouvoir se connecter à la page admin, qui nous permettra d'ajouter du contenu sur le site.

<p align="center">
  <img src="images/admin-page.jpg"
    alt="admin-page">
</p>

Comme tout bon pentester, on va essayer d'injecter du code PHP pour obtenir un shell.

mais d'abbord je voulais tester si on pouvait pas directement utiliser sqlmap pour obtenir un shell.

<p align="center">
  <img src="images/try-os-shell-failed1.jpg"
    alt="try-os-shell-failed1">
</p>
<p align="center">
  <img src="images/try-os-shell-failed2.jpg"
    alt="try-os-shell-failed2">
</p>

C'est un échec, on va revenir à notre idée de base, injecter du code PHP.

<p align="center">
  <img src="images/butnophpupload.jpg"
    alt="butnophpupload">
</p>

On ne peut pas uploader de fichier PHP, Il va falloir comprendre comment le site fait pour detecter les fichiers PHP.

Au début j'ai sorti mon burp suite, pour voir si je pouvais pas intercepter la requête et la modifier pour bypass le filtre.

<p align="center">
  <img src="images/burb-tentative.jpg"
    alt="burb-tentative">
</p>

Mais j'ai vite compris que le site devait vérifier l'extension du fichier, j'ai donc essayé de renommer un fichier PHP en `.php5` pour voir si ça passait (car apache peut executer des fichiers `.php5`).

<p align="center">
  <img src="images/autre-nom-de-fichier.jpg"
    alt="autre-nom-de-fichier">
</p>

On arrive à uploader le fichier, mais il n'est pas executé, apparemment apache n'est pas configuré pour executer les fichiers `.php5`.

J'ai essayé avec d'autres extensions (phtml,inc, php3, php4,cgi, png, jpg, gif, etc...) mais rien n'a fonctionné.

Tentative avec l'extension `.cgi`
<p align="center">
  <img src="images/failed-cgi.jpg"
    alt="failed-cgi">
</p>

Tentative avec l'extension `.inc`
<p align="center">
  <img src="images/test-inc-failed.jpg"
    alt="test-inc-failed">

Tentative avec l'extension `.php5`
<p align="center">
  <img src="images/failed-php5.jpg"
    alt="failed-php5">
</p>

Tentative avec l'extension `.html`
<p align="center">
  <img src="images/html-test.jpg"
    alt="html-test">
</p>

On s'est dit que si le serveur retournait "NO PHP" c'est qu'il devait vérifier que l'extension du fichier soit `.php`. Avec un peu de chance, il ne vérifie qu'en minuscule et que je pourrais donc bypass le filtre en renommant mon fichier en `.PHP`.

<p align="center">
  <img src="images/PHP-en-maj.jpg"
    alt="PHP-en-maj">
</p>

<p align="center">
  <img src="images/php-maj.jpg"
    alt="php-maj">
</p>



En mettant le fichier en majuscule, on arrive à bypass le filtre et à executer notre code PHP.


A partir de là, on a un shell sur la machine quand on va sur le fichier dans `uploads/`. ou quand le serveur essaye d'afficher le contenu du fichier sur la page par default.

<p align="center">
  <img src="images/reverse-shell-qui-fonctionne.jpg"
    alt="shell">
</p>

A partir de là, comme d'habitude, on va utiliser `linpeas.sh` pour voir si on peut trouver des informations intéressantes.

Pour une raison de SSL, je n'ai pas pu le télécharger directement sur la machine, j'ai donc du le télécharger sur ma machine et le transférer sur la machine cible.

<p align="center">
  <img src="images/linpeas.jpg"
    alt="linpeas">
</p>

Mais après avoir regardé les consignes et mis en commun avec les autres, on a vu qu'il n'était pas nécessaire de chercher root et qu'un simple shell était suffisant.
On a donc arrêté là.

Mais vu l'age de la machine ça m'étonnerait pas qu'on puisse exploiter une faille dans l'OS pour obtenir un shell root. (Possiblement dirtycow car la machine est en 2.6.32)

## Conclusion

C'était une machine plutôt simple en ce qui concerne l'exploitation, mais je ne l'ai pas trouvé très intéressante, c'était surtout du `die and retry` pour bypass le filtre. Il y a peu-être une autre méthode mais je n'ai pas trouvé.
