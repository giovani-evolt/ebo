<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251121065625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE settlement ADD csv_id INT NOT NULL');
        $this->addSql('ALTER TABLE settlement ADD CONSTRAINT FK_DD9F1B515684A52A FOREIGN KEY (csv_id) REFERENCES csv (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_DD9F1B515684A52A ON settlement (csv_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE settlement DROP CONSTRAINT FK_DD9F1B515684A52A');
        $this->addSql('DROP INDEX IDX_DD9F1B515684A52A');
        $this->addSql('ALTER TABLE settlement DROP csv_id');
    }
}
