<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210818084100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ichimoku_data CHANGE tenkan tenkan NUMERIC(20, 6) DEFAULT NULL, CHANGE kijun kijun NUMERIC(20, 6) DEFAULT NULL, CHANGE span_a span_a NUMERIC(20, 6) DEFAULT NULL, CHANGE span_b span_b NUMERIC(20, 6) DEFAULT NULL, CHANGE chikou chikou NUMERIC(20, 6) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ichimoku_data CHANGE tenkan tenkan NUMERIC(20, 6) NOT NULL, CHANGE kijun kijun NUMERIC(20, 6) NOT NULL, CHANGE span_a span_a NUMERIC(20, 6) NOT NULL, CHANGE span_b span_b NUMERIC(20, 6) NOT NULL, CHANGE chikou chikou NUMERIC(20, 6) NOT NULL');
    }
}
