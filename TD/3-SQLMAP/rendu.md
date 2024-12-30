# Rendu sur le TD3 SQLMAP

## 2. Using SQLMAP

<p align="center">
  <img src="images/2.1.png" alt="2.1">
</p>

## 3. SQLMAP challenge

J'ai pas utiliser gobuster pour trouver les pages, parce que j'avais pas vu l'indice, j'ai simplement suivie l'exemple.

<p align="center">
  <img src="images/3.3.jpg" alt="3.3">
</p>

On a un formulaire dans lequel on peut mettre un type de sang, et c'est ce formulaire qui est vulnérable à une injection SQL.

<p align="center">
  <img src="images/3.4.jpg" alt="3.4">
</p>

On peut récupérer la requête dans burp pour la donner à sqlmap.
<p align="center">
  <img src="images/3.5.jpg" alt="3.5">
</p>

à partir de là, SQLMAP fonctionne et on peut récupérer les données de la base de données.


<p align="center">
  <img src="images/3.6.jpg" alt="3.6">
</p>

<p align="center">
  <img src="images/3.7.jpg" alt="3.7">
</p>

<p align="center">
  <img src="images/3.8.jpg" alt="3.8">
</p>

<p align="center">
  <img src="images/3.9.jpg" alt="3.9">
</p>

<p align="center">
  <img src="images/3.10.png" alt="3.10">
</p>
