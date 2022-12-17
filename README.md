# wiki_disney
Bienvenue sur notre projet de Wiki Disney, un site crée grâce à la Programmation Orientée Objet en utilisant les technologies suivantes:

- Php avec Symfony
- Twig et l'héritage de templates
- Bootstrap
- SASS

L'objectif de ce site collaboratif est de répertorier les différents personnages de l'univers Disney. Pour cela, les utlisateurs inscrits pourront ajouter les personnages de leur choix et ainsi agrandir chaque jour un peu plus ce catalogue.

## Installation

Pour installer le site localement, merci de suivre les étapes suivantes : 

- Récupérez le projet en local, soit avec fork, soit avec git clone : `git clone https://github.com/HeleneGreat/wiki_disney.git`
- Créez une base de données (avec XAMPP par exemple)
- A la racine du projet, créez un fichier .env.local et configurer l'accès à la base de données sur le modèle suivant : `DATABASE_URL="mysql://user:motdepasse@127.0.0.1:3306/nom_de_la_bdd?serverVersion=mariadb-10.votre.version&charset=utf8mb4"`
- Dans un terminal, se placer dans le projet et faites : `composer install` (installez composer si vous ne l'avez pas) puis `composer update` 
- Si besoin installez node.js, puis faites : `npm install` et `npm run build`
- Installez Symfony si besoin et lancez `symfony server:start`

## Initialisation de la base de données

- Tapez la commande `php bin/console doctrine:migrations:migrate`
- Rendez vous ensuite sur votre navigateur web à l'adresse indiquée dans le retour de la commande précédente, par exemple: https://127.0.0.1:8000, plus précisément à ces adresses, en respectant cet ordre :
  - 127.0.0.1:8000/category/init
  - 127.0.0.1:8000/user/init
  - 127.0.0.1:8000/article/init


- Bienvenue sur le Wiki Disney ! Vos identifiants : email : walt@disney.com // mot de passe : 123456 // mais vous pouvez bien sûr créer votre propre compte.

Bonne navigation !

Hélène Carriou - Alan Dauphin - Emeric Luis

