<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210813112317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE candle_data CHANGE COLUMN `time` `time` DATETIME(6) NULL');
        $this->addSql('ALTER TABLE tick_data CHANGE COLUMN `time` `time` DATETIME(6) NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('ALTER TABLE candle_data CHANGE COLUMN `time` `time` DATETIME NULL');
        $this->addSql('ALTER TABLE tick_data CHANGE COLUMN `time` `time` DATETIME NULL');
    }
}
