<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210818102837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET session rocksdb_bulk_load=1');
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candle_data ADD calculate TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('SET session rocksdb_bulk_load=0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SET session rocksdb_bulk_load=1');
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE candle_data DROP calculate');
        $this->addSql('SET session rocksdb_bulk_load=0');
    }
}
