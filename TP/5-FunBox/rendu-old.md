# Rendu sur la machine FunBOX

## Introduction

Ce document retrace l'exploitation de la machine **FunBOX** dans le cadre du cours de hacking éthique.

---

## Étape 1 : Reconnaissance

### 1.1 Découverte de la machine
![netdiscover](./images/netdiscover.jpg) <br>
La cible a été identifiée à l'adresse IP `192.168.228.133`

### 1.2 Scan des ports ouverts
![nmap](./images/nmap.jpg) <br>
Le scan Nmap a révélé plusieurs ports ouverts, dont :
- **SSH** sur le port **22** (OpenSSH 7.2p2)
- **HTTP** sur le port **80** (Apache httpd 2.4.18)
- **pop3** sur le port **110** (Dovecot pop3d)
- **imap** sur le port **143** (Dovecot imapd)

⚠️ Nous avons eu comme consigne de ne pas se focaliser sur les services `pop3` et `imap` par souci de temps. J'ai fait des recherches sur ces services pour comprendre leur fonctionnement et leurs vulnérabilités, mais je n'y ai pas consacré assez de temps pour vraiment les exploiter. Ces services ne seront donc pas abordés dans ce document.


---

## Étape 2 : Attaque

### 2.1 Apache

En accédant au site web sur le port 80, on découvre qu'il n'y a pas de page d'accueil. On le remarque aussi à cause du retour du script `http-enum` de Nmap.

![default](./images/juste-page-par-default.jpg) <br>

Cela signifie que le site est vide. J'ai passé un peu de temps à tourner en rond. C'est donc a ce moment là que j'ai décidé de me concentrer sur le service SSH (cf 2.2) et de revenir sur le service HTTP plus tard.

En retournant sur vulnHUB pour relire la description de la machine, j'ai vu que le créateur de la machine avait laissé deux indices, le premier que `nikto` était sensible à la casse et le deuxième que le user était accessible au bout de 15 minutes. J'ai passé un petit moment à réfléchir à comment exploiter ces indices.

![vulnhub](./images/hints.jpg) <br>

Finalement j'ai compris que :

- `nikto` était un outil de scan de vulnérabilités web (visiblement sensible à la casse) que je n'utilisais pas de toute façon

- le user est accessible au bout de 15 minutes était probablement un indice sur un bruteforce.

J'ai donc d'abbord essayé l'outil `nikto` en le lançant sur l'adresse IP de la machine. 
![nikto](./images/nikto.jpg) <br>


Evidement l'outil n'a rien trouvé. Mais en raisonant avec des amis, on a compris que des chemins du serveur web pouvaient être en minuscules et que `nikto` ne les trouverait pas. On est donc parti sur un bruteforce de répertoires avec `dirb` qui lui pourrait trouver des répertoires en majuscules et en minuscules.

![dirb](./images/dir-search-min.jpg) <br>

dirb par default ne fait pas la recherche de répertoires en majuscules. J'ai donc relancé la recherche avec l'option `-U` pour inclure les répertoires en majuscules.

![dirb](./images/dirb-search-maj.jpg) <br>

Et là, bingo, on trouve un `ROBOTS.TXT`

![robots](./images/robot-1.jpg) <br>

On tombe sur `upload/` mais cette page est forbidden. 

En regardant le code source de la page, on voit qu'il y a un autre répertoire chaché tout en bas de la page 

![robots](./images/robot-2.jpg) <br>

On se rend donc sur ce répertoire pour ce retrouver encore sur un forbidden. 

![forbidden](./images/forbiden.jpg) <br>

On va donc essayer de bruteforcer les répertoires de ce répertoire avec `dirb` pour voir si on trouve quelque chose.

En minuscule :
![hidden](./images/hiden-upload.jpg) <br>

En majuscule :
![hidden](./images/rien-en-maj.jpg) <br>


On a donc trouvé un répertoire `upload` qui est accessible. On va donc essayer de voir ce qu'on y trouve.

![upload](./images/upload-page.png) <br>

On va y uploader un fichier php pour voir si on avoir accès à l'execution de code et donc à un shell.

![upload](./images/payload-uploaded.png) <br>

On obtient bien un shell quand on va sur le fichier uploadé.

![shell](./images/reverse-shell.jpg)

Gràce à l'attaque sur le service SSH (pardon pour le spoil), on sait qu'il y a un utilisateur `thomas` sur la machine. J'ai donc dans un premier temps cherché à voir si je pouvais trouver des informations sur cet utilisateur dans le répertoire `/home/thomas`.

j'y ai retrouvé un fichier `.todo` qui contient des informations.

![todo](./images/fichier-todo.jpg) <br>

en cherchant un peu plus, j'ai trouvé un fichier `hint.txt`  à la racine.

![hint](./images/hint-txt.jpg) <br>

Commençons par le fichier `hint.txt` qui nous donne des indices.
Déjà il nous fait une metaphore de l'OS avec la barbe de Gandalf, ce qui nous laisse penser que l'OS est vieux et possiblement vulnérable. 
Ensuite la phrase `Now , rockyout.txt isn't your friend, Its a little sed harder :-)`

On sait donc que le fichier `rockyou.txt` est un fichier de wordlist pour des attaque par bruteforce. 

Dit comme ça la phrase veut pas dire grand chose mais la mention du mot `sed` nous laisse penser que le fichier `rockyou.txt` doit être modifié avec `sed` pour être utilisé.

Ensuite on a des textes encodés :

- le premier est en brainfuck (un langage de programmation, merci chatgpt parce que j'avais pas la moindre idée de ce que c'était)

- le deuxième est en base64, le `==` le laisse penser.

- le troisième est en base32 

Au final, les deux premiers sont des fausses pistes, le troisième nous dit d'aller les `todos`, faisant référence au fichier `.todo` que nous avons trouvé dans le répertoire `/home/thomas`.

![base32](./images/base32-hint.jpg) <br>


Avant de me concentrer sur cet histoire de `sed` et de `rockyou.txt`, j'ai lancé un `linpeas.sh` pour voir ce qu'il pouvait trouver.


![linpeas](./images/lenpeas.jpg) <br>

Il nous retourne pas mal d'informations, mais rien de bien intéressant (ou alors je n'ai pas su les exploiter).

Mais on sait que l'OS est vieux et linpeas nous propose des exploits pour notre OS avec des statistiques de réussite.

![linpeas](./images/exploit.jpg) <br>

Mais avant de me lancer dans l'exploitation de ces exploits, je vais me concentrer sur cet histoire de `rockyou.txt` , de `sed` et de `! à ajouter au mot de passe`.

Donc on va juste faire une copie du fichier `rockyou.txt` et on va ajouter un `!` à la fin de chaque mot avec la commande `sed`.

```bash
sed 's/$/!/' rockyou.txt > rockyou2.txt
```

On va ensuite utiliser `hydra` pour faire un bruteforce sur le service SSH avec le fichier `rockyou2.txt` et l'utilisateur `thomas`.

![hydra](./images/brute-force-essai2.jpg) <br>
![hydra-found](./images/FOUND-PASSWORD.jpg) <br>

On a donc trouvé le mot de passe de l'utilisateur `thomas` qui est `thebest!`.

On va donc se connecter en SSH avec cet utilisateur.

![ssh](./images/connecte.jpg) <br>

On va voir les droits sudo de l'utilisateur avec la commande `sudo -l`.

![sudo](./images/pas-de-droit-sudo.jpg) <br>

Dommage, thomas n'a pas de droit sudo. On va donc devoir rester sur notre idée de base de faire un exploit sur l'OS.

En reregardant les exploits proposés par `linpeas`, on voit qu'il y a un exploit pour `dirtycow`. Grâce au liens donné on va retrouver le code de l'exploit. On aura juste à le copier coller dans un fichier et à le compiler.

![dirtycow](./images/exploit-dirty-cow.jpg) <br>

Pour compiler, il nous faut gcc, donc au cas ou on va regarder et prier que gcc soit installé sur la machine.

![gcc](./images/found-gcc.jpg) <br>
C'est assez interessant, car en mettant en commun avec mes amis, on a vu pour certains, ils ne pouvaient avoir accès à gcc (en réinstallant la machine après, j'ai remarqué que je n'avais plus gcc non plus)

Mais pour l'instant on a gcc, on va donc s'en servir pour compiler l'exploit.

![gcc](./images/owned.jpg) <br>

Et voilà, on a un accès root sur la machine....
Du moins pendant 30 secondes, car la machine kernel panic ensuite.

![kernel-panic](./images/kernelpanic.jpg) <br>

Je reboot la machine, et retente l'exploit, et rebelote, cependant après 5 essai j'ai eu le temps de chercher un peu plus sur la machine et j'ai trouvé un fichier `flag.txt` dans le répertoire `/root`.

![flag](./images/flag.jpg) <br>

Après avoir mis en commun avec mes amis, je leurs ai expliqué comment j'ai fais fonctionner dirtycow et ils ont pu le reproduire, mais eux pas de kernel panic, ils ont pu garder l'accès root... étrange.

Apparement c'est pas grave, j'ai quand même réussi 
![pas-grave](./images/KP-pas-grave.png) <br>

Je suis root (30 secondes) donc je suis content.


---

### 2.2 SSH

Cette partie a été faites pendant que je cherchais des informations sur le service HTTP. 

Connaissant la version du service SSH, j'ai cherché des exploits et j'ai vu qu'il y avait une vulnérabilité sur l'enumération des utilisateurs. 

J'ai donc utilisé un exploit de metasploit.

![metasploit](./images/user-found.jpg) <br>
Alors c'est très drôle, parce que j'avais fais un mauvais copier coller ce qui fait que j'ai utiliser le fichier `unix_passwords.txt` comme wordlist pour le bruteforce des utilisateurs, et c'est comme ca que j'ai trouvé l'utilisateur `thomas` sans aucun faux positif.

En mettant en commun avec un ami, on a remarqué qu'on avait des résultats différents, et c'etait probablement du au fait que moi ma VM cible était sous VMWare avec une machine d'attaque WSL, alors que lui avait ses deux machines sur VirtualBox, mes machines étaient donc plus rapides, et étant donné que l'exploit SSH se base sur le temps de réponse, j'ai eu plus de chance de trouver l'utilisateur. Ce qui nous a aussi mis sur la piste c'est que plus tard j'ai trouvé le mot de passe en 5 minutes alors que lui a mis 15 minutes pendant le bruteforce du mot de passe.

Mais bon, à ce moment là je ne connaissais que l'utilisateur `thomas`.

J'ai donc essayé de bruteforcer le mot de passe de l'utilisateur `thomas` avec `hydra` et la wordlist `rockyou.txt`. (evidement je n'avais pas encore connaissances du fichier `hint.txt`)

![hydra](./images/tentative-brute-force.jpg) <br>

Sans succès, après 20 minutes j'ai abandonné et je suis retourné sur le service HTTP.



---

## Conclusion

Cette machine était très intéressante car c'est la premiere fois qu'on fait un exploit sur l'OS directement. Généralement on exploite des services ou des configurations mal sécurisées, mais là on a exploité une faille dans le système d'exploitation lui même. Rien que pour ça, cette machine est ma préférée.

On peut quand même lui reprocher que la partie HTTP était un peu ennuyante, on se retrouve encore dans un espèce de jeu de piste pour trouver des répertoires cachés pour au final uploader un fichier php pour avoir un shell. C'est un peu redondant, mais bon, j'imagine que le but était de nous faire utiliser `dirb` 