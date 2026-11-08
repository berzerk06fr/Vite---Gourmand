<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261106163824 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY fk_avis_utilisateur');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY fk_avis_menu');
        $this->addSql('ALTER TABLE historique_status DROP FOREIGN KEY fk_historique_commande');
        $this->addSql('ALTER TABLE image_menu DROP FOREIGN KEY fk_image_menu');
        $this->addSql('ALTER TABLE menu_regime DROP FOREIGN KEY fk_mr_menu');
        $this->addSql('ALTER TABLE menu_regime DROP FOREIGN KEY fk_mr_regime');
        $this->addSql('ALTER TABLE menu_theme DROP FOREIGN KEY fk_mt_menu');
        $this->addSql('ALTER TABLE menu_theme DROP FOREIGN KEY fk_mt_theme');
        $this->addSql('ALTER TABLE plat_allergene DROP FOREIGN KEY fk_pa_plat');
        $this->addSql('ALTER TABLE plat_allergene DROP FOREIGN KEY fk_pa_allergene');
        $this->addSql('DROP TABLE allergene');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE historique_status');
        $this->addSql('DROP TABLE horaire');
        $this->addSql('DROP TABLE image_menu');
        $this->addSql('DROP TABLE menu_regime');
        $this->addSql('DROP TABLE menu_theme');
        $this->addSql('DROP TABLE plat_allergene');
        $this->addSql('DROP TABLE regime');
        $this->addSql('DROP TABLE theme');
        $this->addSql('DROP INDEX idx_commande_statut ON commande');
        $this->addSql('DROP INDEX idx_commande_date_prestation ON commande');
        $this->addSql('DROP INDEX numero_commande ON commande');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY fk_commande_menu');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY fk_commande_utilisateur');
        $this->addSql('ALTER TABLE commande ADD date_commande DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD statut VARCHAR(50) NOT NULL, DROP prix_unitaire_applique, DROP remise_appliquee, DROP frais_livraison, DROP adresse_livraison, DROP code_postal_livraison, DROP ville_livraison, DROP distance_km, DROP statut_courant, DROP materiel_prete, DROP materiel_rendu, DROP date_limite_restitution, DROP created_at, DROP updated_at, CHANGE numero_commande numero_commande VARCHAR(50) NOT NULL, CHANGE date_prestation date_prevue_livraison DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_commande_utilisateur ON commande');
        $this->addSql('CREATE INDEX IDX_6EEAA67DFB88E14F ON commande (utilisateur_id)');
        $this->addSql('DROP INDEX fk_commande_menu ON commande');
        $this->addSql('CREATE INDEX IDX_6EEAA67DCCD7E912 ON commande (menu_id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT fk_commande_menu FOREIGN KEY (menu_id) REFERENCES menu (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT fk_commande_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('DROP INDEX idx_menu_actif ON menu');
        $this->addSql('ALTER TABLE menu DROP image_url, DROP created_at, DROP updated_at, CHANGE titre titre VARCHAR(255) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE conditions_menu conditions_menu LONGTEXT DEFAULT NULL, CHANGE nb_personnes_min nb_personnes_min INT NOT NULL, CHANGE quantite_stock quantite_stock INT NOT NULL, CHANGE is_actif is_actif TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE menu_plat DROP FOREIGN KEY fk_mp_plat');
        $this->addSql('ALTER TABLE menu_plat DROP FOREIGN KEY fk_mp_plat');
        $this->addSql('ALTER TABLE menu_plat ADD CONSTRAINT FK_E8775249D73DB560 FOREIGN KEY (plat_id) REFERENCES plat (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_mp_plat ON menu_plat');
        $this->addSql('CREATE INDEX IDX_E8775249D73DB560 ON menu_plat (plat_id)');
        $this->addSql('ALTER TABLE menu_plat ADD CONSTRAINT fk_mp_plat FOREIGN KEY (plat_id) REFERENCES plat (id)');
        $this->addSql('ALTER TABLE plat ADD nom VARCHAR(255) NOT NULL, ADD allergenes LONGTEXT DEFAULT NULL, DROP titre, DROP image_url, DROP created_at, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE type type VARCHAR(50) NOT NULL');
        $this->addSql('DROP INDEX idx_utilisateur_email ON utilisateur');
        $this->addSql('ALTER TABLE utilisateur DROP code_postal, DROP ville, DROP is_actif, DROP created_at, DROP updated_at, CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE prenom prenom VARCHAR(255) NOT NULL, CHANGE telephone telephone VARCHAR(20) DEFAULT NULL');
        $this->addSql('DROP INDEX email ON utilisateur');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE allergene (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX nom (nom), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE avis (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, menu_id INT NOT NULL, note TINYINT(1) NOT NULL COMMENT \'Evaluation de 1 à 5\', commentaire TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, is_valide TINYINT(1) DEFAULT 0 NOT NULL COMMENT \'Modéré par le personnel avant affichage public\', created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX fk_avis_utilisateur (utilisateur_id), INDEX idx_avis_menu (menu_id), INDEX idx_avis_valide (is_valide), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, prenom VARCHAR(64) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, sujet VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, message TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, is_traite TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX idx_contact_traite (is_traite), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE historique_status (id INT AUTO_INCREMENT NOT NULL, commande_id INT NOT NULL, statut VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, motif VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'Raison obligatoire en cas d annulation\', date_changement DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX idx_historique_commande (commande_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE horaire (id INT AUTO_INCREMENT NOT NULL, jour_semaine VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, heure_ouverture TIME DEFAULT NULL, heure_fermeture TIME DEFAULT NULL, est_ferme TINYINT(1) DEFAULT 0 NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE image_menu (id INT AUTO_INCREMENT NOT NULL, menu_id INT NOT NULL, url_image VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, titre_alternatif VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'Texte alternatif pour accessibilité RGAA\', is_principale TINYINT(1) DEFAULT 0 NOT NULL, INDEX fk_image_menu (menu_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE menu_regime (menu_id INT NOT NULL, regime_id INT NOT NULL, INDEX fk_mr_regime (regime_id), INDEX IDX_79C112A4CCD7E912 (menu_id), PRIMARY KEY(menu_id, regime_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE menu_theme (menu_id INT NOT NULL, theme_id INT NOT NULL, INDEX fk_mt_theme (theme_id), INDEX IDX_6D9C46FCCD7E912 (menu_id), PRIMARY KEY(menu_id, theme_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE plat_allergene (plat_id INT NOT NULL, allergene_id INT NOT NULL, INDEX fk_pa_allergene (allergene_id), INDEX IDX_6FA44BBFD73DB560 (plat_id), PRIMARY KEY(plat_id, allergene_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE regime (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX libelle (libelle), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE theme (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX libelle (libelle), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT fk_avis_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT fk_avis_menu FOREIGN KEY (menu_id) REFERENCES menu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE historique_status ADD CONSTRAINT fk_historique_commande FOREIGN KEY (commande_id) REFERENCES commande (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE image_menu ADD CONSTRAINT fk_image_menu FOREIGN KEY (menu_id) REFERENCES menu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE menu_regime ADD CONSTRAINT fk_mr_menu FOREIGN KEY (menu_id) REFERENCES menu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE menu_regime ADD CONSTRAINT fk_mr_regime FOREIGN KEY (regime_id) REFERENCES regime (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE menu_theme ADD CONSTRAINT fk_mt_menu FOREIGN KEY (menu_id) REFERENCES menu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE menu_theme ADD CONSTRAINT fk_mt_theme FOREIGN KEY (theme_id) REFERENCES theme (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plat_allergene ADD CONSTRAINT fk_pa_plat FOREIGN KEY (plat_id) REFERENCES plat (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plat_allergene ADD CONSTRAINT fk_pa_allergene FOREIGN KEY (allergene_id) REFERENCES allergene (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DFB88E14F');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DCCD7E912');
        $this->addSql('ALTER TABLE commande ADD prix_unitaire_applique NUMERIC(10, 2) NOT NULL, ADD remise_appliquee NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL COMMENT \'10% si nb_personnes >= nb_personnes_min +5\', ADD frais_livraison NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL COMMENT \'Bordeaux = 0€, autre = 5€ + 0.59€/km\', ADD adresse_livraison VARCHAR(255) NOT NULL, ADD code_postal_livraison VARCHAR(10) NOT NULL, ADD ville_livraison VARCHAR(100) NOT NULL, ADD distance_km NUMERIC(6, 2) DEFAULT \'0.00\' NOT NULL, ADD statut_courant VARCHAR(255) DEFAULT \'en_attente\' NOT NULL, ADD materiel_prete TINYINT(1) DEFAULT 0 NOT NULL, ADD materiel_rendu TINYINT(1) DEFAULT 0 NOT NULL, ADD date_limite_restitution DATE DEFAULT NULL COMMENT \'Restitution sous 10 jours ouvres max\', ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD updated_at DATETIME DEFAULT NULL, DROP date_commande, DROP statut, CHANGE numero_commande numero_commande VARCHAR(20) NOT NULL, CHANGE date_prevue_livraison date_prestation DATETIME NOT NULL');
        $this->addSql('CREATE INDEX idx_commande_statut ON commande (statut_courant)');
        $this->addSql('CREATE INDEX idx_commande_date_prestation ON commande (date_prestation)');
        $this->addSql('CREATE UNIQUE INDEX numero_commande ON commande (numero_commande)');
        $this->addSql('DROP INDEX idx_6eeaa67dccd7e912 ON commande');
        $this->addSql('CREATE INDEX fk_commande_menu ON commande (menu_id)');
        $this->addSql('DROP INDEX idx_6eeaa67dfb88e14f ON commande');
        $this->addSql('CREATE INDEX idx_commande_utilisateur ON commande (utilisateur_id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DCCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id)');
        $this->addSql('ALTER TABLE menu ADD image_url VARCHAR(255) DEFAULT NULL, ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD updated_at DATETIME DEFAULT NULL, CHANGE titre titre VARCHAR(150) NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE conditions_menu conditions_menu TEXT DEFAULT NULL COMMENT \'Conditions et avertissements obligatoires\', CHANGE nb_personnes_min nb_personnes_min INT DEFAULT 1 NOT NULL, CHANGE quantite_stock quantite_stock INT DEFAULT 0 NOT NULL, CHANGE is_actif is_actif TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('CREATE INDEX idx_menu_actif ON menu (is_actif)');
        $this->addSql('ALTER TABLE menu_plat DROP FOREIGN KEY FK_E8775249D73DB560');
        $this->addSql('ALTER TABLE menu_plat DROP FOREIGN KEY FK_E8775249D73DB560');
        $this->addSql('ALTER TABLE menu_plat ADD CONSTRAINT fk_mp_plat FOREIGN KEY (plat_id) REFERENCES plat (id)');
        $this->addSql('DROP INDEX idx_e8775249d73db560 ON menu_plat');
        $this->addSql('CREATE INDEX fk_mp_plat ON menu_plat (plat_id)');
        $this->addSql('ALTER TABLE menu_plat ADD CONSTRAINT FK_E8775249D73DB560 FOREIGN KEY (plat_id) REFERENCES plat (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plat ADD titre VARCHAR(150) NOT NULL, ADD image_url VARCHAR(255) DEFAULT NULL, ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, DROP nom, DROP allergenes, CHANGE description description TEXT DEFAULT NULL, CHANGE type type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE utilisateur ADD code_postal VARCHAR(10) DEFAULT NULL, ADD ville VARCHAR(64) DEFAULT NULL, ADD is_actif TINYINT(1) DEFAULT 1 NOT NULL, ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, ADD updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE nom nom VARCHAR(64) NOT NULL, CHANGE prenom prenom VARCHAR(32) NOT NULL, CHANGE telephone telephone VARCHAR(20) NOT NULL');
        $this->addSql('CREATE INDEX idx_utilisateur_email ON utilisateur (email)');
        $this->addSql('DROP INDEX uniq_1d1c63b3e7927c74 ON utilisateur');
        $this->addSql('CREATE UNIQUE INDEX email ON utilisateur (email)');
    }
}
