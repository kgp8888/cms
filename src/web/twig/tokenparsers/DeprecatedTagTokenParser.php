<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\twig\tokenparsers;

/**
 * Class DeprecatedTagTokenParser
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DeprecatedTagTokenParser extends \Twig_TokenParser
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    private $_tag;

    // Public Methods
    // =========================================================================

    /**
     * Constructor
     *
     * @param string $tag
     */
    public function __construct($tag)
    {
        $this->_tag = $tag;
    }

    /**
     * Parses resource include tags.
     *
     * @param \Twig_Token $token
     *
     * @return \Twig_Node
     */
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        // Parse until we reach the end of this tag
        while (!$stream->test(\Twig_Token::BLOCK_END_TYPE)) {
            $stream->next();
        }

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        // todo: replace with $stream->getSourceContext()->getName() for Twig 2.0
        //       (assuming getSourceContext() is no longer marked @internal)
        /** @noinspection PhpDeprecationInspection */
        $filename = $stream->getFilename();
        \Craft::$app->getDeprecator()->log("{% {$this->_tag} %}", "The {% {$this->_tag} %} tag is no longer necessary. You can remove it from your ‘{$filename}’ template on line {$lineno}.");

        return new \Twig_Node([], [], $lineno, $this->_tag);
    }

    /**
     * Defines the tag name.
     *
     * @return string
     */
    public function getTag()
    {
        return $this->_tag;
    }
}