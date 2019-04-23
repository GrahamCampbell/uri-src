<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri;

use League\Uri\Exception\InvalidUriComponent;

interface PathInterface extends ComponentInterface
{
    /**
     * Returns whether or not the path is absolute or relative.
     */
    public function isAbsolute(): bool;

    /**
     * Returns an instance without dot segments.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the path component normalized by removing
     * the dot segment.
     *
     * @throws InvalidUriComponent for invalid component or transformations
     *                             that would result in a object in invalid state.
     *
     * @return static
     */
    public function withoutDotSegments();

    /**
     * Returns an instance with a leading slash.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the path component with a leading slash
     *
     * @throws InvalidUriComponent for invalid component or transformations
     *                             that would result in a object in invalid state.
     *
     * @return static
     */
    public function withLeadingSlash();

    /**
     * Returns an instance without a leading slash.
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the path component without a leading slash
     *
     * @throws InvalidUriComponent for invalid component or transformations
     *                             that would result in a object in invalid state.
     *
     * @return static
     */
    public function withoutLeadingSlash();
}
