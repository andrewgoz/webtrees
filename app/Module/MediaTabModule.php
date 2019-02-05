<?php
/**
 * webtrees: online genealogy
 * Copyright (C) 2019 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace Fisharebest\Webtrees\Module;

use Fisharebest\Webtrees\Fact;
use Fisharebest\Webtrees\Functions\Functions;
use Fisharebest\Webtrees\Gedcom;
use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Services\ClipboardService;
use Illuminate\Support\Collection;

/**
 * Class MediaTabModule
 */
class MediaTabModule extends AbstractModule implements ModuleTabInterface
{
    use ModuleTabTrait;

    /** @var  Fact[] A list of facts with media objects. */
    private $facts;

    /** @var ClipboardService */
    private $clipboard_service;

    /**
     * NotesTabModule constructor.
     *
     * @param ClipboardService $clipboard_service
     */
    public function __construct (ClipboardService $clipboard_service)
    {
        $this->clipboard_service = $clipboard_service;
    }

    /**
     * How should this module be labelled on tabs, menus, etc.?
     *
     * @return string
     */
    public function title(): string
    {
        /* I18N: Name of a module */
        return I18N::translate('Media');
    }

    /**
     * A sentence describing what this module does.
     *
     * @return string
     */
    public function description(): string
    {
        /* I18N: Description of the “Media” module */
        return I18N::translate('A tab showing the media objects linked to an individual.');
    }

    /**
     * The default position for this tab.  It can be changed in the control panel.
     *
     * @return int
     */
    public function defaultTabOrder(): int
    {
        return 6;
    }

    /** {@inheritdoc} */
    public function hasTabContent(Individual $individual): bool
    {
        return $individual->canEdit() || $this->getFactsWithMedia($individual);
    }

    /** {@inheritdoc} */
    public function isGrayedOut(Individual $individual): bool
    {
        return !$this->getFactsWithMedia($individual);
    }

    /** {@inheritdoc} */
    public function getTabContent(Individual $individual): string
    {
        return view('modules/media/tab', [
            'can_edit'   => $individual->canEdit(),
            'clipboard_facts' => $this->clipboard_service->pastableFactsOfType($individual, $this->supportedFacts()),
            'individual' => $individual,
            'facts'      => $this->getFactsWithMedia($individual),
        ]);
    }

    /**
     * Get all the facts for an individual which contain media objects.
     *
     * @param Individual $individual
     *
     * @return Fact[]
     */
    private function getFactsWithMedia(Individual $individual): array
    {
        if ($this->facts === null) {
            $facts = $individual->facts();
            foreach ($individual->getSpouseFamilies() as $family) {
                if ($family->canShow()) {
                    foreach ($family->facts() as $fact) {
                        $facts[] = $fact;
                    }
                }
            }
            $this->facts = [];
            foreach ($facts as $fact) {
                if (preg_match('/(?:^1|\n\d) OBJE @' . Gedcom::REGEX_XREF . '@/', $fact->gedcom())) {
                    $this->facts[] = $fact;
                }
            }
            Functions::sortFacts($this->facts);
        }

        return $this->facts;
    }

    /** {@inheritdoc} */
    public function canLoadAjax(): bool
    {
        return false;
    }

    /**
     * This module handles the following facts - so don't show them on the "Facts and events" tab.
     *
     * @return Collection|string[]
     */
    public function supportedFacts(): Collection
    {
        return new Collection(['OBJE']);
    }
}
