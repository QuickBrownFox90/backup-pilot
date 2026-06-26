<?php # -*- coding: utf-8 -*-

namespace QBF\BackupPilot\Notice;

use Inpsyde\EnvironmentChecker\Constraints;

/**
 * Class WordPressNotice
 *
 * @package QBF\BackupPilot\Notice
 */
class WordPressNotice extends EnvironmentNotice
{

    const OPTION_NAME = 'backuppilot_notice_wordpress_version';
    const ID = self::OPTION_NAME;

    /**
     * {@inheritdoc}
     */
    protected function getConstraints()
    {
        return [
            new Constraints\WordPressConstraint('5.0'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function message()
    {
        return new NoticeMessage('wordpress');
    }
}
