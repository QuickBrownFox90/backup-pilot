<?php # -*- coding: utf-8 -*-

namespace QBF\BackupPilot\Notice;

use Inpsyde\EnvironmentChecker\Constraints;

/**
 * Class PhpNotice
 *
 * @package QBF\BackupPilot\Notice
 */
class PhpNotice extends EnvironmentNotice
{

    const OPTION_NAME = 'backuppilot_notice_php_version';
    const ID = self::OPTION_NAME;

    /**
     * {@inheritdoc}
     */
    protected function getConstraints()
    {
        return [
            new Constraints\PhpConstraint('8.1'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function message()
    {
        return new NoticeMessage('php');
    }
}
