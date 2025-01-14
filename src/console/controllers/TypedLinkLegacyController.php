<?php
namespace verbb\hyper\console\controllers;

use verbb\hyper\Hyper;

use Craft;
use craft\base\FieldInterface;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\fields\Matrix;
use craft\helpers\Db;
use craft\helpers\Json;

use yii\console\Controller;
use yii\console\ExitCode;

use lenz\linkfield\fields\LinkField;
use lenz\linkfield\records\LinkRecord;

use verbb\supertable\fields\SuperTableField;

class TypedLinkLegacyController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): int
    {
        $this->_updateAllSettings();

        return ExitCode::OK;
    }


    // Private Methods
    // =========================================================================

    private function _updateAllSettings(): void
    {
        Db::update(Table::FIELDS, [
            'type' => 'lenz\\linkfield\\fields\\LinkField',
        ], [
            'type' => 'typedlinkfield\\fields\\LinkField',
        ]);

        $rows = (new Query())
            ->select(['id', 'settings'])
            ->from(Table::FIELDS)
            ->where(['type' => 'lenz\\linkfield\\fields\\LinkField'])
            ->all();

        foreach ($rows as $row) {
            Db::update(Table::FIELDS, [
                'settings' => $this->_updateSettings($row['settings']),
            ], [
                'id' => $row['id'],
            ]);
        }
    }

    private function _updateSettings(string $settings): string
    {
        $settings = Json::decode($settings);
        
        if (!is_array($settings)) {
            $settings = [];
        }

        if (!array_key_exists('typeSettings', $settings)) {
            $settings['typeSettings'] = [];
        }

        $settings['enableAllLinkTypes'] = false;

        if (isset($settings['allowedLinkNames'])) {
            $allowedLinkNames = $settings['allowedLinkNames'];
            
            if (!is_array($allowedLinkNames)) {
                $allowedLinkNames = [$allowedLinkNames];
            }

            foreach ($allowedLinkNames as $linkName) {
                if ($linkName == '*') {
                    $settings['enableAllLinkTypes'] = true;
                } else {
                    $settings['typeSettings'][$linkName]['enabled'] = true;
                }
            }
        }

        unset($settings['allowedLinkNames']);

        return Json::encode($settings);
    }
}
