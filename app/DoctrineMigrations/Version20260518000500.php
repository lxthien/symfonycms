<?php

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;

class Version20260518000500 extends MigrationHelper
{
    public function up(Schema $schema)
    {
        $this->addSql('ALTER TABLE audit_log CHANGE user_id user_id INT DEFAULT NULL, CHANGE entityId entityId VARCHAR(64) DEFAULT NULL, CHANGE entityLabel entityLabel VARCHAR(255) DEFAULT NULL, CHANGE username username VARCHAR(180) DEFAULT NULL, CHANGE ip ip VARCHAR(45) DEFAULT NULL, CHANGE route route VARCHAR(120) DEFAULT NULL, CHANGE requestMethod requestMethod VARCHAR(12) DEFAULT NULL');
        $this->addSql('ALTER TABLE banner CHANGE bannercategory_id bannercategory_id INT DEFAULT NULL, CHANGE position position INT DEFAULT NULL, CHANGE name name VARCHAR(255) DEFAULT NULL, CHANGE url url VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE comment CHANGE comment_id comment_id INT DEFAULT NULL, CHANGE email email VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE contact CHANGE title title VARCHAR(255) DEFAULT NULL, CHANGE phone phone VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE content_revision CHANGE author_id author_id INT DEFAULT NULL, CHANGE title title VARCHAR(255) DEFAULT NULL, CHANGE url url VARCHAR(255) DEFAULT NULL, CHANGE pageTitle pageTitle VARCHAR(255) DEFAULT NULL, CHANGE pageKeyword pageKeyword VARCHAR(255) DEFAULT NULL, CHANGE template template VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE media CHANGE folder_id folder_id INT DEFAULT NULL, CHANGE uploadedBy_id uploadedBy_id INT DEFAULT NULL, CHANGE thumbnailPath thumbnailPath VARCHAR(255) DEFAULT NULL, CHANGE webpPath webpPath VARCHAR(255) DEFAULT NULL, CHANGE width width INT DEFAULT NULL, CHANGE height height INT DEFAULT NULL, CHANGE alt alt VARCHAR(255) DEFAULT NULL, CHANGE credit credit VARCHAR(255) DEFAULT NULL, CHANGE hash hash VARCHAR(64) DEFAULT NULL');
        $this->addSql("ALTER TABLE news CHANGE reviewed_by reviewed_by INT DEFAULT NULL, CHANGE images images VARCHAR(255) DEFAULT NULL, CHANGE status status VARCHAR(20) DEFAULT 'draft' NOT NULL, CHANGE scheduledAt scheduledAt DATETIME DEFAULT NULL, CHANGE publishedAt publishedAt DATETIME DEFAULT NULL, CHANGE previewToken previewToken VARCHAR(64) DEFAULT NULL, CHANGE pageTitle pageTitle VARCHAR(255) DEFAULT NULL, CHANGE pageKeyword pageKeyword VARCHAR(255) DEFAULT NULL, CHANGE ordering ordering INT DEFAULT NULL, CHANGE template template VARCHAR(255) DEFAULT NULL");
        $this->addSql('ALTER TABLE newscategory CHANGE parentcat_id parentcat_id INT DEFAULT NULL, CHANGE pageTitle pageTitle VARCHAR(255) DEFAULT NULL, CHANGE pageKeyword pageKeyword VARCHAR(255) DEFAULT NULL, CHANGE sortBy sortBy VARCHAR(255) DEFAULT NULL, CHANGE titleLandingPage titleLandingPage VARCHAR(255) DEFAULT NULL, CHANGE images images VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE news_media CHANGE altOverride altOverride VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE seo_redirect CHANGE lastAccessedAt lastAccessedAt DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE tag CHANGE pageTitle pageTitle VARCHAR(255) DEFAULT NULL, CHANGE pageKeyword pageKeyword VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE salt salt VARCHAR(255) DEFAULT NULL, CHANGE last_login last_login DATETIME DEFAULT NULL, CHANGE confirmation_token confirmation_token VARCHAR(180) DEFAULT NULL, CHANGE password_requested_at password_requested_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema)
    {
        $this->abortIf(true, 'This migration only normalizes Doctrine metadata and cannot be safely reverted.');
    }
}
