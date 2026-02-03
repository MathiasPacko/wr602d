<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203140922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE generation (id INT AUTO_INCREMENT NOT NULL, file VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_D3266C3BA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE generation_user_contact (generation_id INT NOT NULL, user_contact_id INT NOT NULL, INDEX IDX_59D39840553A6EC4 (generation_id), INDEX IDX_59D3984040C6E3A6 (user_contact_id), PRIMARY KEY (generation_id, user_contact_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE plan (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, limit_generation INT NOT NULL, image VARCHAR(255) DEFAULT NULL, role VARCHAR(50) NOT NULL, price NUMERIC(10, 2) NOT NULL, special_price NUMERIC(10, 2) DEFAULT NULL, special_price_from DATETIME DEFAULT NULL, special_price_to DATETIME DEFAULT NULL, active TINYINT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, lastname VARCHAR(100) NOT NULL, firstname VARCHAR(100) NOT NULL, dob DATE DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, favorite_color VARCHAR(7) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, plan_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX IDX_8D93D649E899029B (plan_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user_contact (id INT AUTO_INCREMENT NOT NULL, lastname VARCHAR(100) NOT NULL, firstname VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, user_id INT NOT NULL, INDEX IDX_146FF832A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE generation ADD CONSTRAINT FK_D3266C3BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE generation_user_contact ADD CONSTRAINT FK_59D39840553A6EC4 FOREIGN KEY (generation_id) REFERENCES generation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE generation_user_contact ADD CONSTRAINT FK_59D3984040C6E3A6 FOREIGN KEY (user_contact_id) REFERENCES user_contact (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649E899029B FOREIGN KEY (plan_id) REFERENCES plan (id)');
        $this->addSql('ALTER TABLE user_contact ADD CONSTRAINT FK_146FF832A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE generation DROP FOREIGN KEY FK_D3266C3BA76ED395');
        $this->addSql('ALTER TABLE generation_user_contact DROP FOREIGN KEY FK_59D39840553A6EC4');
        $this->addSql('ALTER TABLE generation_user_contact DROP FOREIGN KEY FK_59D3984040C6E3A6');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649E899029B');
        $this->addSql('ALTER TABLE user_contact DROP FOREIGN KEY FK_146FF832A76ED395');
        $this->addSql('DROP TABLE generation');
        $this->addSql('DROP TABLE generation_user_contact');
        $this->addSql('DROP TABLE plan');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE user_contact');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
