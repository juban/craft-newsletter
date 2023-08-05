<?php

namespace juban\newsletter\migrations;

use Craft;
use craft\db\Migration;

/**
 * m230729_210357_switch_sendinblue_to_brevo migration.
 */
class m230729_210357_switch_sendinblue_to_brevo extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $adapter = $projectConfig->get('plugins.newsletter.settings.adapterType', true);

        if ($adapter === 'juban\newsletter\adapters\Sendinblue') {
            $projectConfig->set('plugins.newsletter.settings.adapterType', 'juban\newsletter\adapters\Brevo');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230729_210357_switch_sendinblue_to_brevo cannot be reverted.\n";
        return false;
    }
}
