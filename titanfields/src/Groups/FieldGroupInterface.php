<?php

namespace TitanFields\Groups;

use TitanFields\Fields\FieldInterface;

interface FieldGroupInterface
{
    /**
     * Get the unique key for the field group (e.g., group_60a3b2e5f1d4).
     */
    public function getKey(): string;

    /**
     * Get the title of the field group.
     */
    public function getTitle(): string;

    /**
     * Get all fields belonging to this group.
     *
     * @return FieldInterface[]
     */
    public function getFields(): array;

    /**
     * Get the location rules that determine where this group appears.
     *
     * @return array
     */
    public function getLocationRules(): array;

    /**
     * Get the settings for the field group (e.g., position, style, menu_order).
     *
     * @return array
     */
    public function getSettings(): array;

    /**
     * Determine if this group is active.
     */
    public function isActive(): bool;

    /**
     * Transform the group into an array representation for API responses.
     */
    public function toArray(): array;
}
