-- Comunic Structure

SET NAMES utf8;
SET time_zone = '+00:00';


DROP TABLE IF EXISTS `aide`;
CREATE TABLE `aide` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `affiche_acceuil` int(11) NOT NULL DEFAULT '0',
  `aide` longtext NOT NULL,
  `lang` varchar(255) NOT NULL DEFAULT 'fr',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `aime`;
CREATE TABLE `aime` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_type` int(11) NOT NULL,
  `ID_personne` int(11) NOT NULL,
  `Date_envoi` datetime NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'texte',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `amis`;
CREATE TABLE `amis` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_personne` int(11) NOT NULL,
  `ID_amis` int(11) NOT NULL,
  `actif` int(11) NOT NULL DEFAULT '0',
  `abonnement` int(11) NOT NULL DEFAULT '0',
  `autoriser_post_page` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `chat`;
CREATE TABLE `chat` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_personne` int(11) NOT NULL,
  `date_envoi` datetime NOT NULL,
  `message` text NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `chatprive`;
CREATE TABLE `chatprive` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `date_envoi` datetime NOT NULL,
  `ID_personne` int(11) NOT NULL,
  `ID_destination` int(11) NOT NULL,
  `contenu` varchar(255) NOT NULL,
  `vu` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `commentaires`;
CREATE TABLE `commentaires` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_personne` int(11) NOT NULL,
  `ID_texte` int(11) NOT NULL,
  `date_envoi` datetime NOT NULL,
  `time_insert` int(11) DEFAULT NULL,
  `commentaire` varchar(255) NOT NULL,
  `image_commentaire` longtext NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `comunic_API_ServicesToken`;
CREATE TABLE `comunic_API_ServicesToken` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `time_insert` int(11) DEFAULT NULL,
  `serviceName` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `client_domain` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `comunic_API_userLoginToken`;
CREATE TABLE `comunic_API_userLoginToken` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_utilisateurs` int(11) NOT NULL,
  `ID_comunic_API_ServicesToken` int(11) NOT NULL,
  `token1` varchar(255) NOT NULL,
  `token2` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `comunic_conversations_list`;
CREATE TABLE `comunic_conversations_list` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_utilisateurs` int(11) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `last_active` int(11) DEFAULT NULL,
  `creation_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `comunic_conversations_messages`;
CREATE TABLE `comunic_conversations_messages` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_comunic_conversations_list` int(11) DEFAULT NULL,
  `ID_utilisateurs` int(11) DEFAULT NULL,
  `time_insert` int(11) DEFAULT NULL,
  `message` varchar(200) DEFAULT NULL,
  `image_path` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `comunic_conversations_users`;
CREATE TABLE `comunic_conversations_users` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_comunic_conversations_list` int(11) DEFAULT NULL,
  `ID_utilisateurs` int(11) DEFAULT NULL,
  `time_add` int(11) DEFAULT NULL,
  `following` int(1) DEFAULT '0',
  `saw_last_message` int(1) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `comunic_notifications`;
CREATE TABLE `comunic_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time_create` int(11) DEFAULT NULL,
  `seen` int(1) DEFAULT '0',
  `from_user_id` int(11) DEFAULT NULL,
  `dest_user_id` int(11) DEFAULT NULL,
  `on_elem_id` int(11) DEFAULT NULL,
  `on_elem_type` varchar(25) DEFAULT NULL,
  `type` varchar(25) DEFAULT NULL,
  `visibility` varchar(20) DEFAULT NULL,
  `from_container_id` int(11) DEFAULT NULL,
  `from_container_type` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `contact`;
CREATE TABLE `contact` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_personne` int(255) NOT NULL,
  `date_envoi` datetime NOT NULL,
  `ID_type` int(11) NOT NULL DEFAULT '1',
  `texte` text NOT NULL,
  `vu` int(11) NOT NULL DEFAULT '0',
  `mail_personne` varchar(255) NOT NULL,
  `IP_personne` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `forum_reponse`;
CREATE TABLE `forum_reponse` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_personne` int(11) NOT NULL,
  `ID_sujet` int(11) NOT NULL,
  `date_envoi` datetime NOT NULL,
  `reponse` longtext NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `forum_sujet`;
CREATE TABLE `forum_sujet` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `ID_personne` int(11) NOT NULL,
  `date_envoi` datetime NOT NULL,
  `sujet` longtext NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `galerie_video`;
CREATE TABLE `galerie_video` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `URL` varchar(255) NOT NULL,
  `ID_user` int(11) NOT NULL,
  `nom_video` varchar(255) NOT NULL,
  `file_type` varchar(255) NOT NULL,
  `size` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `groupe_personnes`;
CREATE TABLE `groupe_personnes` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_personne` int(11) NOT NULL,
  `liste_ID` longtext NOT NULL,
  `date_ajout` datetime NOT NULL,
  `nom` varchar(255) NOT NULL DEFAULT 'Groupe sans nom',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `log_admin`;
CREATE TABLE `log_admin` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `type_admin` varchar(255) NOT NULL,
  `date_envoi` datetime NOT NULL,
  `adresse` varchar(255) NOT NULL,
  `nom_admin` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `messagerie`;
CREATE TABLE `messagerie` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_expediteur` int(11) NOT NULL,
  `ID_destinataire` int(11) NOT NULL,
  `objet` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `date_envoi` datetime NOT NULL,
  `lu` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `minifyURL`;
CREATE TABLE `minifyURL` (
  `ID` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `date_ajout` datetime NOT NULL,
  `auto_redirect` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `multi_login`;
CREATE TABLE `multi_login` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_personne` int(11) NOT NULL,
  `ID_target` int(11) NOT NULL,
  `allowed` int(11) NOT NULL DEFAULT '1',
  `date_ajout` datetime NOT NULL,
  `IP_ajout` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `notification`;
CREATE TABLE `notification` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_personne` int(11) NOT NULL,
  `date_envoi` datetime NOT NULL,
  `message` varchar(255) NOT NULL,
  `ID_createur` int(11) NOT NULL,
  `vu` int(11) NOT NULL DEFAULT '0',
  `adresse` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `id_type` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `pages`;
CREATE TABLE `pages` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_personne` int(11) NOT NULL,
  `nom_page` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `scout_abonnement`;
CREATE TABLE `scout_abonnement` (
  `ID` tinyint(4) NOT NULL AUTO_INCREMENT,
  `ID_personne` int(11) NOT NULL,
  `ID_groupe` varchar(255) NOT NULL,
  `ID_patrouille` varchar(255) NOT NULL,
  `niveau_abonnement` int(11) NOT NULL,
  `date_ajout` datetime NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `scout_commentaires`;
CREATE TABLE `scout_commentaires` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_personne` int(11) NOT NULL,
  `ID_groupe` varchar(255) NOT NULL,
  `ID_evenement` varchar(255) NOT NULL,
  `date_ajout` datetime NOT NULL,
  `commentaire` text NOT NULL,
  `url_pdf` varchar(255) NOT NULL,
  `url_img` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `scout_droit_groupe`;
CREATE TABLE `scout_droit_groupe` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_personne` int(11) NOT NULL,
  `ID_groupe` varchar(255) NOT NULL,
  `niveau_droit` int(11) NOT NULL DEFAULT '2',
  `date_creation` datetime NOT NULL,
  `valide` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `scout_evenements`;
CREATE TABLE `scout_evenements` (
  `ID` varchar(255) NOT NULL,
  `ID_groupe` varchar(255) NOT NULL,
  `ID_createur` int(11) NOT NULL,
  `date_ajout` datetime NOT NULL,
  `nom` varchar(255) NOT NULL,
  `transport` varchar(255) NOT NULL,
  `choix_categorie` varchar(255) NOT NULL,
  `lieu_depart` varchar(255) NOT NULL,
  `lieu_evenement` varchar(255) NOT NULL,
  `date_depart` varchar(255) NOT NULL,
  `date_fin` varchar(255) NOT NULL,
  `heure_depart` int(11) NOT NULL,
  `minute_depart` int(11) NOT NULL,
  `heure_fin` int(11) NOT NULL,
  `minute_fin` int(11) NOT NULL,
  `details` longtext NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `scout_groupes`;
CREATE TABLE `scout_groupes` (
  `ID` varchar(255) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `ville` varchar(255) NOT NULL,
  `code_postal` int(11) NOT NULL,
  `ID_createur` int(11) NOT NULL,
  `date_creation` int(11) NOT NULL,
  `description` longtext NOT NULL,
  `groupe_verifie` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID` (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `scout_patrouilles`;
CREATE TABLE `scout_patrouilles` (
  `ID` varchar(255) NOT NULL,
  `ID_createur` int(11) NOT NULL,
  `ID_groupe` varchar(255) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `ID_type_patrouille` int(11) NOT NULL,
  `description` longtext NOT NULL,
  `date_creation` datetime NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ID` (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `scout_type_patrouille`;
CREATE TABLE `scout_type_patrouille` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `determinant` varchar(255) NOT NULL,
  `nom_type` varchar(255) NOT NULL,
  `nom_personnes` varchar(255) NOT NULL,
  `sexe` varchar(255) NOT NULL DEFAULT 'h',
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `sondage`;
CREATE TABLE `sondage` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_utilisateurs` int(11) NOT NULL,
  `ID_texte` int(11) NOT NULL,
  `date_creation` datetime NOT NULL,
  `question` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `sondage_choix`;
CREATE TABLE `sondage_choix` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_sondage` int(11) NOT NULL,
  `date_creation` datetime NOT NULL,
  `Choix` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `sondage_reponse`;
CREATE TABLE `sondage_reponse` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_utilisateurs` int(11) NOT NULL,
  `ID_sondage` int(11) NOT NULL,
  `ID_sondage_choix` int(11) NOT NULL,
  `date_envoi` datetime NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `sujet_contact`;
CREATE TABLE `sujet_contact` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `nom_fr` varchar(255) NOT NULL,
  `nom_en` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `texte`;
CREATE TABLE `texte` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_personne` int(11) NOT NULL,
  `date_envoi` datetime NOT NULL,
  `time_insert` int(11) DEFAULT NULL,
  `texte` text NOT NULL,
  `ID_amis` int(11) NOT NULL DEFAULT '0',
  `niveau_visibilite` varchar(255) NOT NULL DEFAULT '1',
  `type` varchar(255) NOT NULL DEFAULT 'texte',
  `idvideo` int(11) DEFAULT NULL,
  `size` varchar(255) DEFAULT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `annee_fin` varchar(255) DEFAULT NULL,
  `mois_fin` varchar(255) DEFAULT NULL,
  `jour_fin` varchar(255) DEFAULT NULL,
  `time_end` int(11) DEFAULT NULL,
  `url_page` varchar(255) DEFAULT NULL,
  `titre_page` varchar(255) DEFAULT NULL,
  `description_page` longtext,
  `image_page` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE `utilisateurs` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `date_creation` datetime NOT NULL,
  `mail` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `affiche_chat` int(11) NOT NULL DEFAULT '0',
  `public` int(11) NOT NULL DEFAULT '0',
  `pageouverte` int(11) NOT NULL DEFAULT '0',
  `question1` varchar(255) DEFAULT NULL,
  `reponse1` varchar(255) DEFAULT NULL,
  `question2` varchar(255) DEFAULT NULL,
  `reponse2` varchar(255) DEFAULT NULL,
  `bloquecommentaire` int(11) NOT NULL DEFAULT '0',
  `last_activity` int(11) NOT NULL DEFAULT '1',
  `bloquenotification` int(11) NOT NULL DEFAULT '1',
  `bloque_son_notification` int(11) NOT NULL DEFAULT '1',
  `old_menu` int(11) NOT NULL DEFAULT '0',
  `volet_amis_ouvert` int(11) NOT NULL DEFAULT '1',
  `autoriser_post_amis` int(11) NOT NULL DEFAULT '1',
  `color_menu` varchar(255) NOT NULL DEFAULT 'blue',
  `autorise_mail` int(11) NOT NULL DEFAULT '1',
  `mode_pages` int(11) NOT NULL DEFAULT '0',
  `sous_repertoire` varchar(255) DEFAULT NULL,
  `acces_ecolev2` varchar(1) NOT NULL DEFAULT '0',
  `view_private_chat` int(1) NOT NULL DEFAULT '1',
  `height_private_chat` int(11) NOT NULL DEFAULT '210',
  `nettoyage_automatique_notifications` int(11) NOT NULL DEFAULT '1',
  `heure_nettoyage_automatique_notifications` int(11) NOT NULL DEFAULT '0',
  `jour_nettoyage_automatique_notifications` int(11) NOT NULL DEFAULT '0',
  `mois_nettoyage_automatique_notifications` int(11) NOT NULL DEFAULT '2',
  `page_verifiee` int(11) NOT NULL DEFAULT '0',
  `site_web` varchar(255) NOT NULL DEFAULT '',
  `liste_amis_publique` varchar(1) NOT NULL DEFAULT '1',
  `new_password` varchar(255) DEFAULT NULL,
  `flux_rss` varchar(255) DEFAULT NULL,
  `vu_message_info_fil` varchar(255) NOT NULL DEFAULT '0',
  `allow_multilogin` int(11) NOT NULL DEFAULT '0',
  `allow_piwik` int(11) NOT NULL DEFAULT '1',
  `public_note` varchar(255) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_token_time_create` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
