<?php # -*- coding: utf-8 -*-

namespace QBF\BackupPilot\Settings;

/**
 * Class SettingsUpdatable
 */
interface SettingUpdatable {

    public function update();

    public function reset();
}
