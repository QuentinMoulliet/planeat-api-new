<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261009062305 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema (Planeat light)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, sort_order INT NOT NULL, UNIQUE INDEX UNIQ_CATEGORY_NAME (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE household (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE meal (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, seasons JSON DEFAULT NULL, is_favorite TINYINT NOT NULL, created_at DATETIME NOT NULL, household_id INT NOT NULL, INDEX IDX_9EF68E9CE79FF843 (household_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE meal_product (meal_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_AAF9B6B4639666D6 (meal_id), INDEX IDX_AAF9B6B44584665A (product_id), PRIMARY KEY (meal_id, product_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE meal_plan (id INT AUTO_INCREMENT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, created_at DATETIME NOT NULL, household_id INT NOT NULL, INDEX IDX_C7848889E79FF843 (household_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE planned_meal (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, slot VARCHAR(10) NOT NULL, is_locked TINYINT NOT NULL, meal_plan_id INT NOT NULL, meal_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_PLANNED_MEAL_SLOT (meal_plan_id, date, slot), INDEX IDX_25AEE301912AB082 (meal_plan_id), INDEX IDX_25AEE301639666D6 (meal_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, normalized_name VARCHAR(100) NOT NULL, is_staple TINYINT NOT NULL, created_at DATETIME NOT NULL, household_id INT NOT NULL, category_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_PRODUCT_HOUSEHOLD_NAME (household_id, normalized_name), INDEX IDX_D34A04ADE79FF843 (household_id), INDEX IDX_D34A04AD12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shopping_list (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, household_id INT NOT NULL, UNIQUE INDEX UNIQ_SHOPPING_LIST_HOUSEHOLD (household_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shopping_list_item (id INT AUTO_INCREMENT NOT NULL, custom_name VARCHAR(100) DEFAULT NULL, quantity INT NOT NULL, is_checked TINYINT NOT NULL, is_manual TINYINT NOT NULL, sources JSON DEFAULT NULL, shopping_list_id INT NOT NULL, product_id INT DEFAULT NULL, category_id INT DEFAULT NULL, INDEX IDX_4FB1C22423245BF9 (shopping_list_id), INDEX IDX_4FB1C2244584665A (product_id), INDEX IDX_4FB1C22412469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, household_id INT NOT NULL, UNIQUE INDEX UNIQ_USER_EMAIL (email), INDEX IDX_8D93D649E79FF843 (household_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE meal ADD CONSTRAINT FK_9EF68E9CE79FF843 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE meal_product ADD CONSTRAINT FK_AAF9B6B4639666D6 FOREIGN KEY (meal_id) REFERENCES meal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE meal_product ADD CONSTRAINT FK_AAF9B6B44584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE meal_plan ADD CONSTRAINT FK_C7848889E79FF843 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planned_meal ADD CONSTRAINT FK_25AEE301912AB082 FOREIGN KEY (meal_plan_id) REFERENCES meal_plan (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planned_meal ADD CONSTRAINT FK_25AEE301639666D6 FOREIGN KEY (meal_id) REFERENCES meal (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADE79FF843 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE shopping_list ADD CONSTRAINT FK_3DC1A459E79FF843 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shopping_list_item ADD CONSTRAINT FK_4FB1C22423245BF9 FOREIGN KEY (shopping_list_id) REFERENCES shopping_list (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shopping_list_item ADD CONSTRAINT FK_4FB1C2244584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE shopping_list_item ADD CONSTRAINT FK_4FB1C22412469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D649E79FF843 FOREIGN KEY (household_id) REFERENCES household (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE meal DROP FOREIGN KEY FK_9EF68E9CE79FF843');
        $this->addSql('ALTER TABLE meal_product DROP FOREIGN KEY FK_AAF9B6B4639666D6');
        $this->addSql('ALTER TABLE meal_product DROP FOREIGN KEY FK_AAF9B6B44584665A');
        $this->addSql('ALTER TABLE meal_plan DROP FOREIGN KEY FK_C7848889E79FF843');
        $this->addSql('ALTER TABLE planned_meal DROP FOREIGN KEY FK_25AEE301912AB082');
        $this->addSql('ALTER TABLE planned_meal DROP FOREIGN KEY FK_25AEE301639666D6');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADE79FF843');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE shopping_list DROP FOREIGN KEY FK_3DC1A459E79FF843');
        $this->addSql('ALTER TABLE shopping_list_item DROP FOREIGN KEY FK_4FB1C22423245BF9');
        $this->addSql('ALTER TABLE shopping_list_item DROP FOREIGN KEY FK_4FB1C2244584665A');
        $this->addSql('ALTER TABLE shopping_list_item DROP FOREIGN KEY FK_4FB1C22412469DE2');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649E79FF843');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE household');
        $this->addSql('DROP TABLE meal');
        $this->addSql('DROP TABLE meal_product');
        $this->addSql('DROP TABLE meal_plan');
        $this->addSql('DROP TABLE planned_meal');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE shopping_list');
        $this->addSql('DROP TABLE shopping_list_item');
        $this->addSql('DROP TABLE `user`');
    }
}
