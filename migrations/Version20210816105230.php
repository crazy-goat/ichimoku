<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210816105230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('SET session rocksdb_bulk_load=1');
        $this->addSql('ALTER TABLE candle_data ENGINE=ROCKSDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin');
        $this->addSql('ALTER TABLE tick_data ENGINE=ROCKSDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_bin');
        $this->addSql('SET session rocksdb_bulk_load=0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candle_data ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
        $this->addSql('ALTER TABLE tick_data ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
    }
}
