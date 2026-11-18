-- =====================================================================
--  Vite & Gourmand - Création de la base relationnelle (MySQL 8 / MariaDB 10.6+)
--  Écrit à la main (sans migration ni ORM) pour démontrer la maîtrise du SQL.
--
--  Améliorations par rapport au MCD de l'annexe :
--   * montants en DECIMAL(10,2) au lieu de DOUBLE (pas d'erreur d'arrondi sur de l'argent)
--   * photos stockées comme chemin de fichier (menu_image) au lieu de BLOB
--   * table commande_suivi : historique horodaté de chaque changement de statut
--   * avis rattaché à la commande (1 avis maximum par commande terminée)
--   * utilisateur.actif : désactivation d'un employé sans supprimer son historique
--   * plat.type (entrée / plat / dessert), motif + mode de contact d'annulation
--   * password_reset : jetons de réinitialisation hachés, à usage unique et limités dans le temps
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS connexion_echec, password_reset, avis, commande_suivi, commande,
    plat_allergene, menu_plat, menu_image, allergene, plat, menu, regime, theme,
    horaire, utilisateur, role;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Rôles et utilisateurs
-- ---------------------------------------------------------------------
CREATE TABLE role (
    role_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    libelle  VARCHAR(50)  NOT NULL,
    PRIMARY KEY (role_id),
    UNIQUE KEY uq_role_libelle (libelle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE utilisateur (
    utilisateur_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    role_id         INT UNSIGNED NOT NULL,
    email           VARCHAR(180) NOT NULL,
    password        VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt/argon2 (password_hash), jamais le mot de passe en clair',
    nom             VARCHAR(80)  NOT NULL,
    prenom          VARCHAR(80)  NOT NULL,
    telephone       VARCHAR(20)  NULL,
    adresse_postale VARCHAR(255) NULL,
    code_postal     CHAR(5)      NULL,
    ville           VARCHAR(100) NULL,
    actif           TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (utilisateur_id),
    UNIQUE KEY uq_utilisateur_email (email),
    CONSTRAINT fk_utilisateur_role FOREIGN KEY (role_id) REFERENCES role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset (
    reset_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    utilisateur_id  INT UNSIGNED NOT NULL,
    token_hash      CHAR(64)     NOT NULL COMMENT 'SHA-256 du jeton envoyé par mail',
    expires_at      DATETIME     NOT NULL,
    used_at         DATETIME     NULL,
    PRIMARY KEY (reset_id),
    UNIQUE KEY uq_reset_token (token_hash),
    CONSTRAINT fk_reset_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (utilisateur_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Limitation des tentatives de connexion (anti force brute)
CREATE TABLE connexion_echec (
    echec_id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ip          VARCHAR(45)  NOT NULL,
    email       VARCHAR(180) NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (echec_id),
    KEY idx_echec_ip_date (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Catalogue : thèmes, régimes, menus, plats, allergènes
-- ---------------------------------------------------------------------
CREATE TABLE theme (
    theme_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    libelle  VARCHAR(50)  NOT NULL,
    PRIMARY KEY (theme_id),
    UNIQUE KEY uq_theme_libelle (libelle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE regime (
    regime_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    libelle   VARCHAR(50)  NOT NULL,
    PRIMARY KEY (regime_id),
    UNIQUE KEY uq_regime_libelle (libelle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menu (
    menu_id                  INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    titre                    VARCHAR(100)  NOT NULL,
    description              TEXT          NOT NULL,
    theme_id                 INT UNSIGNED  NOT NULL,
    regime_id                INT UNSIGNED  NOT NULL,
    nombre_personne_minimum  INT UNSIGNED  NOT NULL,
    prix_minimum             DECIMAL(10,2) NOT NULL COMMENT 'Prix pour le nombre minimum de personnes',
    conditions               TEXT          NOT NULL,
    quantite_restante        INT UNSIGNED  NOT NULL DEFAULT 0 COMMENT 'Stock : nombre de commandes encore possibles',
    actif                    TINYINT(1)    NOT NULL DEFAULT 1,
    created_at               DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (menu_id),
    KEY idx_menu_prix (prix_minimum),
    CONSTRAINT fk_menu_theme  FOREIGN KEY (theme_id)  REFERENCES theme (theme_id),
    CONSTRAINT fk_menu_regime FOREIGN KEY (regime_id) REFERENCES regime (regime_id),
    CONSTRAINT chk_menu_min   CHECK (nombre_personne_minimum >= 1),
    CONSTRAINT chk_menu_prix  CHECK (prix_minimum >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menu_image (
    image_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    menu_id    INT UNSIGNED NOT NULL,
    chemin     VARCHAR(255) NOT NULL,
    texte_alt  VARCHAR(150) NOT NULL COMMENT 'Texte alternatif obligatoire (RGAA)',
    ordre      SMALLINT     NOT NULL DEFAULT 0,
    PRIMARY KEY (image_id),
    CONSTRAINT fk_image_menu FOREIGN KEY (menu_id) REFERENCES menu (menu_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plat (
    plat_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    titre       VARCHAR(100) NOT NULL,
    type        ENUM('entree','plat','dessert') NOT NULL,
    description VARCHAR(255) NULL,
    PRIMARY KEY (plat_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Un plat peut appartenir à plusieurs menus (association N-N)
CREATE TABLE menu_plat (
    menu_id INT UNSIGNED NOT NULL,
    plat_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (menu_id, plat_id),
    CONSTRAINT fk_mp_menu FOREIGN KEY (menu_id) REFERENCES menu (menu_id) ON DELETE CASCADE,
    CONSTRAINT fk_mp_plat FOREIGN KEY (plat_id) REFERENCES plat (plat_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE allergene (
    allergene_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    libelle      VARCHAR(50)  NOT NULL,
    PRIMARY KEY (allergene_id),
    UNIQUE KEY uq_allergene_libelle (libelle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE plat_allergene (
    plat_id      INT UNSIGNED NOT NULL,
    allergene_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (plat_id, allergene_id),
    CONSTRAINT fk_pa_plat      FOREIGN KEY (plat_id)      REFERENCES plat (plat_id) ON DELETE CASCADE,
    CONSTRAINT fk_pa_allergene FOREIGN KEY (allergene_id) REFERENCES allergene (allergene_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Horaires (affichés dans le pied de page, modifiables par les employés)
-- ---------------------------------------------------------------------
CREATE TABLE horaire (
    horaire_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    jour            VARCHAR(10)  NOT NULL,
    ordre           TINYINT      NOT NULL COMMENT '1 = lundi ... 7 = dimanche',
    heure_ouverture TIME         NULL,
    heure_fermeture TIME         NULL,
    ferme           TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (horaire_id),
    UNIQUE KEY uq_horaire_jour (jour)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Commandes, suivi et avis
-- ---------------------------------------------------------------------
CREATE TABLE commande (
    commande_id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    numero_commande        VARCHAR(20)   NOT NULL,
    utilisateur_id         INT UNSIGNED  NOT NULL,
    menu_id                INT UNSIGNED  NOT NULL,
    date_commande          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_prestation        DATE          NOT NULL,
    heure_livraison        TIME          NOT NULL,
    adresse_prestation     VARCHAR(255)  NOT NULL,
    code_postal_prestation CHAR(5)       NOT NULL,
    ville_prestation       VARCHAR(100)  NOT NULL,
    nombre_personne        INT UNSIGNED  NOT NULL,
    prix_menu              DECIMAL(10,2) NOT NULL COMMENT 'Prix du menu avant remise',
    remise                 DECIMAL(10,2) NOT NULL DEFAULT 0,
    distance_km            DECIMAL(7,2)  NOT NULL DEFAULT 0,
    prix_livraison         DECIMAL(10,2) NOT NULL DEFAULT 0,
    prix_total             DECIMAL(10,2) NOT NULL,
    statut                 ENUM('en_attente','accepte','en_preparation','en_cours_de_livraison','livre',
                                'en_attente_retour_materiel','terminee','annulee') NOT NULL DEFAULT 'en_attente',
    pret_materiel          TINYINT(1)    NOT NULL DEFAULT 0,
    restitution_materiel   TINYINT(1)    NOT NULL DEFAULT 0,
    mode_contact_annulation ENUM('gsm','mail') NULL,
    motif_annulation       VARCHAR(255)  NULL,
    PRIMARY KEY (commande_id),
    UNIQUE KEY uq_commande_numero (numero_commande),
    KEY idx_commande_statut (statut),
    KEY idx_commande_date (date_commande),
    CONSTRAINT fk_commande_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (utilisateur_id),
    CONSTRAINT fk_commande_menu        FOREIGN KEY (menu_id)        REFERENCES menu (menu_id),
    CONSTRAINT chk_commande_personnes  CHECK (nombre_personne >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE commande_suivi (
    suivi_id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    commande_id       INT UNSIGNED NOT NULL,
    statut            VARCHAR(40)  NOT NULL,
    date_modification DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modifie_par       INT UNSIGNED NULL COMMENT 'Utilisateur ayant effectué le changement (traçabilité)',
    commentaire       VARCHAR(255) NULL,
    PRIMARY KEY (suivi_id),
    KEY idx_suivi_commande (commande_id),
    CONSTRAINT fk_suivi_commande FOREIGN KEY (commande_id) REFERENCES commande (commande_id) ON DELETE CASCADE,
    CONSTRAINT fk_suivi_auteur   FOREIGN KEY (modifie_par) REFERENCES utilisateur (utilisateur_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE avis (
    avis_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    commande_id    INT UNSIGNED NOT NULL,
    utilisateur_id INT UNSIGNED NOT NULL,
    note           TINYINT UNSIGNED NOT NULL,
    commentaire    VARCHAR(1000) NOT NULL,
    statut         ENUM('en_attente','valide','refuse') NOT NULL DEFAULT 'en_attente',
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (avis_id),
    UNIQUE KEY uq_avis_commande (commande_id),
    CONSTRAINT fk_avis_commande    FOREIGN KEY (commande_id)    REFERENCES commande (commande_id) ON DELETE CASCADE,
    CONSTRAINT fk_avis_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (utilisateur_id) ON DELETE CASCADE,
    CONSTRAINT chk_avis_note CHECK (note BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
