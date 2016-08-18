<?php
namespace ElevenLabs\Api\Service\Collection;

interface Pagination
{
    /**
     * @return int
     */
    public function getTotalPages();

    /**
     * @return int
     */
    public function getTotalEntries();

    /**
     * @return int
     */
    public function getPerPage();

    /**
     * @return bool
     */
    public function hasLinks();

    /**
     * @return PaginationLinks
     */
    public function getLinks();
}