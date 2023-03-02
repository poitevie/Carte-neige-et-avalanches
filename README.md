<a name="readme-top"></a>
<!--
*** Template: https://github.com/othneildrew/Best-README-Template
-->

<!-- PROJECT LOGO -->
<br />
<div align="center">

<a>
    <img src="https://skitour.fr/img/skitour.png" alt="Logo" width="256">
  </a>

  <h1 align="center">Skitour - Carte neige et avalanches</h3>

  <p align="center">
    Une carte interactive pour le site Skitour, affichant la neige fraîche, totale et le risque nivologique à partir des bulletins Météo France.
    <br />
    <a href="https://github.com/poitevie/Skitour"><strong>Documentation »</strong></a>
    <br />
    <br />
  </p>
</div>

<!-- TABLE OF CONTENTS -->
<details>
  <summary>Sommaire</summary>
  <ol>
    <li>
      <a href="#about-the-project">A propos du projet</a>
      <ul>
        <li><a href="#built-with">Technologies</a></li>
      </ul>
    </li>
    <li>
      <a href="#getting-started">Lancer le projet</a>
      <ul>
        <li><a href="#prerequisites">Prérequis</a></li>
        <li><a href="#installation">Installation</a></li>
      </ul>
    </li>
    <li><a href="#contact">Contact</a></li>
    <li><a href="#developpeurs">Développeurs</a></li>
  </ol>
</details>

<!-- ABOUT THE PROJECT -->
## A propos du projet

[![Product Name Screen Shot][product-screenshot]](https://skitour.fr/)  

Ce projet a pour but de fournir un aperçu des informations neige et avalanches sur le site [Skitour](https://skitour.fr/), pour permettre aux utilisateurs de préparer au mieux leurs sorties.

Ces informations sont récupérées à partir des bulletins journaliers des risques d'avalanches ([BRA](https://meteofrance.com/meteo-montagne)) de Météo France, puis affichées sur une carte interactive à l'aide de l'outil Leaflet sur différentes couches :
* Neige fraîchement tombée
* Neige totale
* Risque nivologique

### Technologies

Liste des technologies utilisées pour le projet :

* <img src="https://upload.wikimedia.org/wikipedia/commons/2/27/PHP-logo.svg" width="80" style="display: block; margin-bottom: 16px"/>
* <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/1/13/Leaflet_logo.svg/320px-Leaflet_logo.svg.png" width="80" style="display: block; margin-bottom: 16px"/>
* <img src="https://upload.wikimedia.org/wikipedia/commons/6/6a/JavaScript-logo.png" width="40" style="display: block; margin-bottom: 16px"/>

<!-- GETTING STARTED -->
## Lancer le projet

Pour lancer le projet, suivez les instructions suivantes.

### Prérequis

* Installer PHP
  ```sh
  sudo apt-get install php
  ```

### Installation

1. Cloner le repository
   ```sh
   git clone git@github.com:poitevie/Skitour.git
   ```
2. Lancer le serveur PHP en local à la racine du repository
   ```sh
   php -S localhost:8000
   ```
3. Avant d'utiliser l'application, il faut que les fichiers binaires et les images soient générés en suivant les étapes décrites dans [generation.md](docs/generation.md).
4. Au besoin éditer la variable `urlBack` dans le fichier `index.html` pour que les appels au back-end s'effectuent correctement.
5. Sur la carte, il est possible de zoomer, dézoomer, se déplacer et cliquer sur un massif pour afficher les informations sur la neige et le risque nivologique.
6. Les informations affichées pour un massif sont la hauteur de neige fraîche, les prévisions de neige fraîche, la hauteur de neige totale, le risque nivologique, les orientations, les pentes et les altitudes.
7. Ouvrir un navigateur, puis accéder à l'url `http://localhost:8000`

<!-- CONTACT -->
## Contact

Admin - https://skitour.fr/mailto.php?id=admin

<!-- DEVELOPPEURS -->
## Développeurs
* Eve POITEVIN
* Thomas FOURNIER
* Julien GUIGNARD
* Thomas BACH

<p align="right">(<a href="#readme-top">back to top</a>)</p>

[product-screenshot]: https://skitour.fr/img/bandeau.jpg
