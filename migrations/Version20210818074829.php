<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210818074829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ichimoku_data (symbol VARCHAR(16) NOT NULL, time DATETIME NOT NULL, period VARCHAR(2) NOT NULL, tenkan NUMERIC(20, 6) NOT NULL, kijun NUMERIC(20, 6) NOT NULL, span_a NUMERIC(20, 6) NOT NULL, span_b NUMERIC(20, 6) NOT NULL, chikou NUMERIC(20, 6) NOT NULL, PRIMARY KEY(symbol, time, period)) ENGINE=ROCKSDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE ichimoku_data');
    }
}
