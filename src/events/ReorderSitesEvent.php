<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

use yii\base\Event;

/**
 * Reorder Sites event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ReorderSitesEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var integer[] The site IDs in their new order
     */
    public $siteIds;
}