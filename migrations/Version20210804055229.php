<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210804055229 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE candle_data (symbol VARCHAR(16) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, time DATETIME NOT NULL, period VARCHAR(2) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, open NUMERIC(20, 6) NOT NULL, high NUMERIC(20, 6) NOT NULL, low NUMERIC(20, 6) NOT NULL, close NUMERIC(20, 6) NOT NULL, PRIMARY KEY(symbol, time, period)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tick_data (symbol VARCHAR(16) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, time DATETIME NOT NULL, bid NUMERIC(20, 6) NOT NULL, ask NUMERIC(20, 6) NOT NULL, PRIMARY KEY(symbol, time)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE candle_data');
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tick_data');
    }
}
