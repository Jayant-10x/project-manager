<?php

namespace App\Livewire\Projects;

use App\Models\Milestone;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Services\AuditNotificationService;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectMilestoneModal extends Component
{
    public ?int $projectId = null;

    public string $projectCode = '';

    public bool $hasExistingMilestones = false;

    public array $items = [];

    #[On('open-milestone-modal')]
    public function load(int $projectId): void
    {
        $this->resetValidation();
        $this->projectId = $projectId;
        $project = Project::findOrFail($projectId);
        $this->projectCode = $project->code;

        $termsByMilestone = PaymentTerm::where(function ($termQuery) use ($projectId) {
            $termQuery->where('project_id', $projectId)
                ->orWhereIn('milestone_id', Milestone::where('project_id', $projectId)->pluck('id'));
        })
            ->whereNotNull('milestone_id')
            ->get(['id', 'milestone_id', 'label', 'seq', 'amount', 'received', 'tds', 'status', 'due_date'])
            ->groupBy('milestone_id');

        $dbItems = $project->milestones()->orderBy('sort_order')->get();
        $this->hasExistingMilestones = $dbItems->isNotEmpty();
        if ($dbItems->isEmpty()) {
            $deliveryDate = $project->delivery_date?->toDateString() ?? '';
            $this->items = [
                ['id' => null, 'name' => 'Kickoff & requirement gathering', 'dueDate' => '', 'status' => 'Pending', 'done' => false, 'done_on' => null, 'chase_notes' => null, 'next_chase_date' => null, 'locked' => false, 'lockedReason' => null, 'connectedTerms' => []],
                ['id' => null, 'name' => 'Design approval', 'dueDate' => '', 'status' => 'Pending', 'done' => false, 'done_on' => null, 'chase_notes' => null, 'next_chase_date' => null, 'locked' => false, 'lockedReason' => null, 'connectedTerms' => []],
                ['id' => null, 'name' => 'Development complete', 'dueDate' => '', 'status' => 'Pending', 'done' => false, 'done_on' => null, 'chase_notes' => null, 'next_chase_date' => null, 'locked' => false, 'lockedReason' => null, 'connectedTerms' => []],
                ['id' => null, 'name' => 'UAT & sign-off', 'dueDate' => '', 'status' => 'Pending', 'done' => false, 'done_on' => null, 'chase_notes' => null, 'next_chase_date' => null, 'locked' => false, 'lockedReason' => null, 'connectedTerms' => []],
                ['id' => null, 'name' => 'Live / delivery', 'dueDate' => $deliveryDate, 'status' => 'Pending', 'done' => false, 'done_on' => null, 'chase_notes' => null, 'next_chase_date' => null, 'locked' => false, 'lockedReason' => null, 'connectedTerms' => []],
            ];
        } else {
            $this->items = $dbItems->map(function ($milestone) use ($termsByMilestone) {
                $linkedTerms = $termsByMilestone->get($milestone->id);
                $isLocked = ! empty($linkedTerms) && $linkedTerms->isNotEmpty();
                $connectedTerms = [];

                if ($isLocked) {
                    $connectedTerms = $linkedTerms->map(fn ($term) => [
                        'id' => $term->id,
                        'seq' => $term->seq,
                        'label' => $term->label,
                        'amount' => (float) $term->amount,
                        'received' => (float) $term->received,
                        'tds' => (float) $term->tds,
                        'due' => (float) $term->balance,
                        'status' => $term->status ?? 'Pending',
                        'dueDate' => $term->due_date?->toDateString(),
                    ])->values()->toArray();

                    $lockedReason = 'Connected to payment schedule: '.$linkedTerms->map(fn ($term) => "#{$term->seq} {$term->label}")->implode(', ');
                } else {
                    $lockedReason = null;
                }

                return [
                    'id' => $milestone->id,
                    'name' => $milestone->name,
                    'dueDate' => $milestone->due_date?->toDateString() ?? '',
                    'status' => $milestone->status,
                    'done' => (bool) $milestone->done,
                    'done_on' => $milestone->done_on?->toDateString(),
                    'chase_notes' => $milestone->chase_notes,
                    'next_chase_date' => $milestone->next_chase_date?->toDateString(),
                    'locked' => $isLocked,
                    'lockedReason' => $lockedReason,
                    'connectedTerms' => $connectedTerms,
                ];
            })->toArray();
        }

        $this->dispatch('open-modal', 'project-milestone-modal');
    }

    public function loadStandardTemplate(string $department = 'default'): void
    {
        $project = Project::find($this->projectId);
        $deliveryDate = $project?->delivery_date?->toDateString() ?? '';

        // 1. Preserve any existing locked milestones connected to payment schedule
        $lockedItems = array_values(array_filter($this->items, fn ($milestoneItem) => ! empty($milestoneItem['locked'])));

        $standardStages = match ($department) {
            /** *1. E-Commerce Management*
             *
             * Milestone 1: Kickoff & requirement gathering
             *
             * Milestone 2: Catalog & inventory setup
             *
             * Milestone 3: Payment & shipping configuration
             *
             * Milestone 4: End-to-end checkout & order testing
             *
             * Milestone 5: Live store launch & operations sign-off
             */
            'e-commerce' => [
                ['name' => 'Kickoff & requirement gathering', 'dueDate' => ''],
                ['name' => 'Catalog & inventory setup', 'dueDate' => ''],
                ['name' => 'Payment & shipping configuration', 'dueDate' => ''],
                ['name' => 'End-to-end checkout & order testing', 'dueDate' => ''],
                ['name' => 'Live store launch & operations sign-off', 'dueDate' => $deliveryDate],
            ],
            /***2. Website Development*
             *
             * Milestone 1: Kickoff & requirement gathering
             *
             *  Milestone 2: Wireframe & design approval
             *
             *  Milestone 3: Development complete
             *
             *  Milestone 4: QA, speed & device testing
             *
             *  Milestone 5: UAT & sign-off
             *
             *  Milestone 6: Production launch & DNS go-live*/
            'website-development' => [
                ['name' => 'Kickoff & requirement gathering', 'dueDate' => ''],
                ['name' => 'Wireframe & design approval', 'dueDate' => ''],
                ['name' => 'Development complete', 'dueDate' => ''],
                ['name' => 'QA, speed & device testing', 'dueDate' => ''],
                ['name' => 'UAT & sign-off', 'dueDate' => ''],
                ['name' => 'Production launch & DNS go-live', 'dueDate' => $deliveryDate],
            ],
            /** *3. Ads Management*
             *
             * Milestone 1: Kickoff & ad account access setup
             *
             *  Milestone 2: Tracking pixel & conversion setup
             *
             *  Milestone 3: Campaign strategy & audience mapping
             *
             *  Milestone 4: Ad staging & budget allocation
             *
             *  Milestone 5: Campaign launch & initial review*/
            'ads-management' => [
                ['name' => 'Kickoff & ad account access setup', 'dueDate' => ''],
                ['name' => 'Tracking pixel & conversion setup', 'dueDate' => ''],
                ['name' => 'Campaign strategy & audience mapping', 'dueDate' => ''],
                ['name' => 'Ad staging & budget allocation', 'dueDate' => ''],
                ['name' => 'Campaign launch & initial review', 'dueDate' => $deliveryDate],
            ],
            /** *4. D2C Ads*
             *
             * Milestone 1: Kickoff, offer & margin planning
             *
             * Milestone 2: Creative hook & angle approval
             *
             * Milestone 3: Ad creative & landing page build
             *
             * Milestone 4: Retargeting & funnel setup
             *
             * Milestone 5: Campaign launch & creative testing*/
            'd2c-ads' => [
                ['name' => 'Kickoff, offer & margin planning', 'dueDate' => ''],
                ['name' => 'Creative hook & angle approval', 'dueDate' => ''],
                ['name' => 'Ad creative & landing page build', 'dueDate' => ''],
                ['name' => 'Retargeting & funnel setup', 'dueDate' => ''],
                ['name' => 'Campaign launch & creative testing', 'dueDate' => $deliveryDate],
            ],
            /** *5. SEO*
             *
             * Milestone 1: Kickoff & technical audit
             *
             *  Milestone 2: Keyword research & content plan approval
             *
             *  Milestone 3: On-page & technical SEO implementation
             *
             *  Milestone 4: Sitemaps, robots.txt & schema setup
             *
             *  Milestone 5: Search console indexing & live sign-off*/
            'seo' => [
                ['name' => 'Kickoff & technical audit', 'dueDate' => ''],
                ['name' => 'Keyword research & content plan approval', 'dueDate' => ''],
                ['name' => 'On-page & technical SEO implementation', 'dueDate' => ''],
                ['name' => 'Sitemaps, robots.txt & schema setup', 'dueDate' => ''],
                ['name' => 'Search console indexing & live sign-off', 'dueDate' => $deliveryDate],
            ],
            /** *6. Video Editing*
             *
             * Milestone 1: Creative brief & asset ingestion
             *
             *  Milestone 2: Storyboard & hook approval
             *
             *  Milestone 3: First cut video delivery
             *
             *  Milestone 4: Revisions & compliance sign-off
             *
             *  Milestone 5: Final multi-format exports & asset handover*/
            'video-editing' => [
                ['name' => 'Creative brief & asset ingestion', 'dueDate' => ''],
                ['name' => 'Storyboard & hook approval', 'dueDate' => ''],
                ['name' => 'First cut video delivery', 'dueDate' => ''],
                ['name' => 'Revisions & compliance sign-off', 'dueDate' => ''],
                ['name' => 'Final multi-format exports & asset handover', 'dueDate' => $deliveryDate],
            ],
            /** *7. Software Tools / CRM*
             *  Milestone 1: Kickoff & data requirement gathering
             *
             *  Milestone 2: CRM setup & customer property mapping
             *
             *  Milestone 3: Tool integration & data sync complete
             *
             *  Milestone 4: Automation workflows & trigger testing
             *
             *  Milestone 5: Live deployment & team training sign-off
             */
            'software-tools-crm' => [
                ['name' => 'Kickoff & data requirement gathering', 'dueDate' => ''],
                ['name' => 'CRM setup & customer property mapping', 'dueDate' => ''],
                ['name' => 'Tool integration & data sync complete', 'dueDate' => ''],
                ['name' => 'Automation workflows & trigger testing', 'dueDate' => ''],
                ['name' => 'Live deployment & team training sign-off', 'dueDate' => $deliveryDate],
            ],
            'default' => [
                ['name' => 'Kickoff & requirement gathering', 'dueDate' => ''],
                ['name' => 'Design approval', 'dueDate' => ''],
                ['name' => 'Development complete', 'dueDate' => ''],
                ['name' => 'UAT & sign-off', 'dueDate' => ''],
                ['name' => 'Live / delivery', 'dueDate' => $deliveryDate],
            ],
        };

        if (empty($lockedItems)) {
            $this->items = array_map(fn ($stage) => [
                'id' => null,
                'name' => $stage['name'],
                'dueDate' => $stage['dueDate'],
                'status' => 'Pending',
                'done' => false,
                'done_on' => null,
                'chase_notes' => null,
                'next_chase_date' => null,
                'locked' => false,
                'lockedReason' => null,
                'connectedTerms' => [],
            ], $standardStages);
        } else {
            // Locked milestones exist: Preserve locked milestones and add any missing standard template stages
            $lockedNames = array_map(fn ($milestoneItem) => strtolower(trim($milestoneItem['name'])), $lockedItems);
            $newStages = [];

            foreach ($standardStages as $stage) {
                if (in_array(strtolower(trim($stage['name'])), $lockedNames, true)) {
                    continue;
                }

                $newStages[] = [
                    'id' => null,
                    'name' => $stage['name'],
                    'dueDate' => $stage['dueDate'],
                    'status' => 'Pending',
                    'done' => false,
                    'done_on' => null,
                    'chase_notes' => null,
                    'next_chase_date' => null,
                    'locked' => false,
                    'lockedReason' => null,
                    'connectedTerms' => [],
                ];
            }

            $this->items = array_merge($lockedItems, $newStages);

            $this->dispatch('toast', [
                'msg' => 'Preserved '.count($lockedItems).' locked milestone(s) connected to payment schedule.',
                'type' => 'ok',
            ]);
        }

        $this->resetValidation();
    }

    /*public function loadECommerceTemplate(): void {
        $project = Project::find($this->projectId);
        $deliveryDate = $project?->delivery_date?->toDateString() ?? '';

        // 1. Preserve any existing locked milestones connected to payment schedule
        $lockedItems = array_values(array_filter($this->items, fn ($milestoneItem) => ! empty($milestoneItem['locked'])));

        $standardStages = [
            ['name' => 'Kickoff & requirement gathering', 'dueDate' => ''],
            ['name' => 'Catalog & inventory setup', 'dueDate' => ''],
            ['name' => 'Payment & shipping configuration', 'dueDate' => ''],
            ['name' => 'End-to-end checkout & order testing', 'dueDate' => ''],
            ['name' => 'Live store launch & operations sign-off', 'dueDate' => $deliveryDate],
        ];

        if (empty($lockedItems)) {
            $this->items = array_map(fn($stage) => [
                'id' => null,
                'name' => $stage['name'],
                'dueDate' => $stage['dueDate'],
                'status' => 'Pending',
                'done' => false,
                'done_on' => null,
                'chase_notes' => null,
                'next_chase_date' => null,
                'locked' => false,
                'lockedReason' => null,
                'connectedTerms' => [],
            ], $standardStages);
        } else {
            // Locked milestones exist: Preserve locked milestones and add any missing standard template stages
            $lockedNames = array_map(fn ($milestoneItem) => strtolower(trim($milestoneItem['name'])), $lockedItems);
            $newStages = [];

            foreach ($standardStages as $stage) {
                if (in_array(strtolower(trim($stage['name'])), $lockedNames, true)) {
                    continue;
                }

                $newStages[] = [
                    'id' => null,
                    'name' => $stage['name'],
                    'dueDate' => $stage['dueDate'],
                    'status' => 'Pending',
                    'done' => false,
                    'done_on' => null,
                    'chase_notes' => null,
                    'next_chase_date' => null,
                    'locked' => false,
                    'lockedReason' => null,
                    'connectedTerms' => [],
                ];
            }

            $this->items = array_merge($lockedItems, $newStages);

            $this->dispatch('toast', [
                'msg' => 'Preserved ' . count($lockedItems) . ' locked milestone(s) connected to payment schedule.',
                'type' => 'ok',
            ]);
        }

        $this->resetValidation();
    }

    public function loadWebsiteDevelopmentTemplate(): void {
        $project = Project::find($this->projectId);
        $deliveryDate = $project?->delivery_date?->toDateString() ?? '';

        // 1. Preserve any existing locked milestones connected to payment schedule
        $lockedItems = array_values(array_filter($this->items, fn ($milestoneItem) => ! empty($milestoneItem['locked'])));
        /**
         * 2. Website Development*
         *  Milestone 1: Kickoff & requirement gathering
         *
         *  Milestone 2: Wireframe & design approval
         *
         *  Milestone 3: Development complete
         *
         *  Milestone 4: QA, speed & device testing
         *
         *  Milestone 5: UAT & sign-off
         *
         *  Milestone 6: Production launch & DNS go-live
        $standardStages = [
            ['name' => 'Kickoff & requirement gathering', 'dueDate' => ''],
            ['name' => 'Wireframe & design approval', 'dueDate' => ''],
            ['name' => 'Development complete', 'dueDate' => ''],
            ['name' => 'QA, speed & device testing', 'dueDate' => ''],
            ['name' => 'UAT & sign-off', 'dueDate' => ''],
            ['name' => 'Production launch & DNS go-live', 'dueDate' => $deliveryDate],
        ];

        if (empty($lockedItems)) {
            $this->items = array_map(fn($stage) => [
                'id' => null,
                'name' => $stage['name'],
                'dueDate' => $stage['dueDate'],
                'status' => 'Pending',
                'done' => false,
                'done_on' => null,
                'chase_notes' => null,
                'next_chase_date' => null,
                'locked' => false,
                'lockedReason' => null,
                'connectedTerms' => [],
            ], $standardStages);
        } else {
            // Locked milestones exist: Preserve locked milestones and add any missing standard template stages
            $lockedNames = array_map(fn ($milestoneItem) => strtolower(trim($milestoneItem['name'])), $lockedItems);
            $newStages = [];

            foreach ($standardStages as $stage) {
                if (in_array(strtolower(trim($stage['name'])), $lockedNames, true)) {
                    continue;
                }

                $newStages[] = [
                    'id' => null,
                    'name' => $stage['name'],
                    'dueDate' => $stage['dueDate'],
                    'status' => 'Pending',
                    'done' => false,
                    'done_on' => null,
                    'chase_notes' => null,
                    'next_chase_date' => null,
                    'locked' => false,
                    'lockedReason' => null,
                    'connectedTerms' => [],
                ];
            }

            $this->items = array_merge($lockedItems, $newStages);

            $this->dispatch('toast', [
                'msg' => 'Preserved ' . count($lockedItems) . ' locked milestone(s) connected to payment schedule.',
                'type' => 'ok',
            ]);
        }

        $this->resetValidation();
    }

    public function loadAdsManagementTemplate(): void {
        $project = Project::find($this->projectId);
        $deliveryDate = $project?->delivery_date?->toDateString() ?? '';

        // 1. Preserve any existing locked milestones connected to payment schedule
        $lockedItems = array_values(array_filter($this->items, fn ($milestoneItem) => ! empty($milestoneItem['locked'])));
        /**
         * 3. Ads Management*
         *  Milestone 1: Kickoff & ad account access setup
         *
         *  Milestone 2: Tracking pixel & conversion setup
         *
         *  Milestone 3: Campaign strategy & audience mapping
         *
         *  Milestone 4: Ad staging & budget allocation
         *
         *  Milestone 5: Campaign launch & initial review
        $standardStages = [
            ['name' => 'Kickoff & ad account access setup', 'dueDate' => ''],
            ['name' => 'Tracking pixel & conversion setup', 'dueDate' => ''],
            ['name' => 'Campaign strategy & audience mapping', 'dueDate' => ''],
            ['name' => 'Ad staging & budget allocation', 'dueDate' => ''],
            ['name' => 'Campaign launch & initial review', 'dueDate' => $deliveryDate],
        ];

        if (empty($lockedItems)) {
            $this->items = array_map(fn($stage) => [
                'id' => null,
                'name' => $stage['name'],
                'dueDate' => $stage['dueDate'],
                'status' => 'Pending',
                'done' => false,
                'done_on' => null,
                'chase_notes' => null,
                'next_chase_date' => null,
                'locked' => false,
                'lockedReason' => null,
                'connectedTerms' => [],
            ], $standardStages);
        } else {
            // Locked milestones exist: Preserve locked milestones and add any missing standard template stages
            $lockedNames = array_map(fn ($milestoneItem) => strtolower(trim($milestoneItem['name'])), $lockedItems);
            $newStages = [];

            foreach ($standardStages as $stage) {
                if (in_array(strtolower(trim($stage['name'])), $lockedNames, true)) {
                    continue;
                }

                $newStages[] = [
                    'id' => null,
                    'name' => $stage['name'],
                    'dueDate' => $stage['dueDate'],
                    'status' => 'Pending',
                    'done' => false,
                    'done_on' => null,
                    'chase_notes' => null,
                    'next_chase_date' => null,
                    'locked' => false,
                    'lockedReason' => null,
                    'connectedTerms' => [],
                ];
            }

            $this->items = array_merge($lockedItems, $newStages);

            $this->dispatch('toast', [
                'msg' => 'Preserved ' . count($lockedItems) . ' locked milestone(s) connected to payment schedule.',
                'type' => 'ok',
            ]);
        }

        $this->resetValidation();
    }

    public function loadD2CAdsTemplate(): void {
        $project = Project::find($this->projectId);
        $deliveryDate = $project?->delivery_date?->toDateString() ?? '';

        // 1. Preserve any existing locked milestones connected to payment schedule
        $lockedItems = array_values(array_filter($this->items, fn ($milestoneItem) => ! empty($milestoneItem['locked'])));
        /**
         * *4. D2C Ads*
         *
         * Milestone 1: Kickoff, offer & margin planning
         *
         * Milestone 2: Creative hook & angle approval
         *
         * Milestone 3: Ad creative & landing page build
         *
         * Milestone 4: Retargeting & funnel setup
         *
         * Milestone 5: Campaign launch & creative testing
        $standardStages = [
            ['name' => 'Kickoff, offer & margin planning', 'dueDate' => ''],
            ['name' => 'Creative hook & angle approval', 'dueDate' => ''],
            ['name' => 'Ad creative & landing page build', 'dueDate' => ''],
            ['name' => 'Retargeting & funnel setup', 'dueDate' => ''],
            ['name' => 'Campaign launch & creative testing', 'dueDate' => $deliveryDate],
        ];

        if (empty($lockedItems)) {
            $this->items = array_map(fn($stage) => [
                'id' => null,
                'name' => $stage['name'],
                'dueDate' => $stage['dueDate'],
                'status' => 'Pending',
                'done' => false,
                'done_on' => null,
                'chase_notes' => null,
                'next_chase_date' => null,
                'locked' => false,
                'lockedReason' => null,
                'connectedTerms' => [],
            ], $standardStages);
        } else {
            // Locked milestones exist: Preserve locked milestones and add any missing standard template stages
            $lockedNames = array_map(fn ($milestoneItem) => strtolower(trim($milestoneItem['name'])), $lockedItems);
            $newStages = [];

            foreach ($standardStages as $stage) {
                if (in_array(strtolower(trim($stage['name'])), $lockedNames, true)) {
                    continue;
                }

                $newStages[] = [
                    'id' => null,
                    'name' => $stage['name'],
                    'dueDate' => $stage['dueDate'],
                    'status' => 'Pending',
                    'done' => false,
                    'done_on' => null,
                    'chase_notes' => null,
                    'next_chase_date' => null,
                    'locked' => false,
                    'lockedReason' => null,
                    'connectedTerms' => [],
                ];
            }

            $this->items = array_merge($lockedItems, $newStages);

            $this->dispatch('toast', [
                'msg' => 'Preserved ' . count($lockedItems) . ' locked milestone(s) connected to payment schedule.',
                'type' => 'ok',
            ]);
        }

        $this->resetValidation();
    }

    public function loadSEOTemplate(): void {
        $project = Project::find($this->projectId);
        $deliveryDate = $project?->delivery_date?->toDateString() ?? '';

        // 1. Preserve any existing locked milestones connected to payment schedule
        $lockedItems = array_values(array_filter($this->items, fn ($milestoneItem) => ! empty($milestoneItem['locked'])));
        /**
         * 5. SEO*
         *  Milestone 1: Kickoff & technical audit
         *
         *  Milestone 2: Keyword research & content plan approval
         *
         *  Milestone 3: On-page & technical SEO implementation
         *
         *  Milestone 4: Sitemaps, robots.txt & schema setup
         *
         *  Milestone 5: Search console indexing & live sign-off
        $standardStages = [
            ['name' => 'Kickoff & technical audit', 'dueDate' => ''],
            ['name' => 'Keyword research & content plan approval', 'dueDate' => ''],
            ['name' => 'On-page & technical SEO implementation', 'dueDate' => ''],
            ['name' => 'Sitemaps, robots.txt & schema setup', 'dueDate' => ''],
            ['name' => 'Search console indexing & live sign-off', 'dueDate' => $deliveryDate],
        ];

        if (empty($lockedItems)) {
            $this->items = array_map(fn($stage) => [
                'id' => null,
                'name' => $stage['name'],
                'dueDate' => $stage['dueDate'],
                'status' => 'Pending',
                'done' => false,
                'done_on' => null,
                'chase_notes' => null,
                'next_chase_date' => null,
                'locked' => false,
                'lockedReason' => null,
                'connectedTerms' => [],
            ], $standardStages);
        } else {
            // Locked milestones exist: Preserve locked milestones and add any missing standard template stages
            $lockedNames = array_map(fn ($milestoneItem) => strtolower(trim($milestoneItem['name'])), $lockedItems);
            $newStages = [];

            foreach ($standardStages as $stage) {
                if (in_array(strtolower(trim($stage['name'])), $lockedNames, true)) {
                    continue;
                }

                $newStages[] = [
                    'id' => null,
                    'name' => $stage['name'],
                    'dueDate' => $stage['dueDate'],
                    'status' => 'Pending',
                    'done' => false,
                    'done_on' => null,
                    'chase_notes' => null,
                    'next_chase_date' => null,
                    'locked' => false,
                    'lockedReason' => null,
                    'connectedTerms' => [],
                ];
            }

            $this->items = array_merge($lockedItems, $newStages);

            $this->dispatch('toast', [
                'msg' => 'Preserved ' . count($lockedItems) . ' locked milestone(s) connected to payment schedule.',
                'type' => 'ok',
            ]);
        }

        $this->resetValidation();
    }

    public function loadVideoEditingTemplate(): void {
        $project = Project::find($this->projectId);
        $deliveryDate = $project?->delivery_date?->toDateString() ?? '';

        // 1. Preserve any existing locked milestones connected to payment schedule
        $lockedItems = array_values(array_filter($this->items, fn ($milestoneItem) => ! empty($milestoneItem['locked'])));
        /**
         * *6. Video Editing*
         *
         * Milestone 1: Creative brief & asset ingestion
         *
         *  Milestone 2: Storyboard & hook approval
         *
         *  Milestone 3: First cut video delivery
         *
         *  Milestone 4: Revisions & compliance sign-off
         *
         *  Milestone 5: Final multi-format exports & asset handover
        $standardStages = [
            ['name' => 'Creative brief & asset ingestion', 'dueDate' => ''],
            ['name' => 'Storyboard & hook approval', 'dueDate' => ''],
            ['name' => 'First cut video delivery', 'dueDate' => ''],
            ['name' => 'Revisions & compliance sign-off', 'dueDate' => ''],
            ['name' => 'Final multi-format exports & asset handover', 'dueDate' => $deliveryDate],
        ];

        if (empty($lockedItems)) {
            $this->items = array_map(fn($stage) => [
                'id' => null,
                'name' => $stage['name'],
                'dueDate' => $stage['dueDate'],
                'status' => 'Pending',
                'done' => false,
                'done_on' => null,
                'chase_notes' => null,
                'next_chase_date' => null,
                'locked' => false,
                'lockedReason' => null,
                'connectedTerms' => [],
            ], $standardStages);
        } else {
            // Locked milestones exist: Preserve locked milestones and add any missing standard template stages
            $lockedNames = array_map(fn ($milestoneItem) => strtolower(trim($milestoneItem['name'])), $lockedItems);
            $newStages = [];

            foreach ($standardStages as $stage) {
                if (in_array(strtolower(trim($stage['name'])), $lockedNames, true)) {
                    continue;
                }

                $newStages[] = [
                    'id' => null,
                    'name' => $stage['name'],
                    'dueDate' => $stage['dueDate'],
                    'status' => 'Pending',
                    'done' => false,
                    'done_on' => null,
                    'chase_notes' => null,
                    'next_chase_date' => null,
                    'locked' => false,
                    'lockedReason' => null,
                    'connectedTerms' => [],
                ];
            }

            $this->items = array_merge($lockedItems, $newStages);

            $this->dispatch('toast', [
                'msg' => 'Preserved ' . count($lockedItems) . ' locked milestone(s) connected to payment schedule.',
                'type' => 'ok',
            ]);
        }

        $this->resetValidation();
    }

    public function loadSoftwareToolsCRMTemplate(): void {
        $project = Project::find($this->projectId);
        $deliveryDate = $project?->delivery_date?->toDateString() ?? '';

        // 1. Preserve any existing locked milestones connected to payment schedule
        $lockedItems = array_values(array_filter($this->items, fn ($milestoneItem) => ! empty($milestoneItem['locked'])));
        /**
         *7. Software Tools / CRM*
         *  Milestone 1: Kickoff & data requirement gathering
         *
         *  Milestone 2: CRM setup & customer property mapping
         *
         *  Milestone 3: Tool integration & data sync complete
         *
         *  Milestone 4: Automation workflows & trigger testing
         *
         *  Milestone 5: Live deployment & team training sign-off

        $standardStages = [
            ['name' => 'Kickoff & data requirement gathering', 'dueDate' => ''],
            ['name' => 'CRM setup & customer property mapping', 'dueDate' => ''],
            ['name' => 'Tool integration & data sync complete', 'dueDate' => ''],
            ['name' => 'Automation workflows & trigger testing', 'dueDate' => ''],
            ['name' => 'Live deployment & team training sign-off', 'dueDate' => $deliveryDate],
        ];

        if (empty($lockedItems)) {
            $this->items = array_map(fn($stage) => [
                'id' => null,
                'name' => $stage['name'],
                'dueDate' => $stage['dueDate'],
                'status' => 'Pending',
                'done' => false,
                'done_on' => null,
                'chase_notes' => null,
                'next_chase_date' => null,
                'locked' => false,
                'lockedReason' => null,
                'connectedTerms' => [],
            ], $standardStages);
        } else {
            // Locked milestones exist: Preserve locked milestones and add any missing standard template stages
            $lockedNames = array_map(fn ($milestoneItem) => strtolower(trim($milestoneItem['name'])), $lockedItems);
            $newStages = [];

            foreach ($standardStages as $stage) {
                if (in_array(strtolower(trim($stage['name'])), $lockedNames, true)) {
                    continue;
                }

                $newStages[] = [
                    'id' => null,
                    'name' => $stage['name'],
                    'dueDate' => $stage['dueDate'],
                    'status' => 'Pending',
                    'done' => false,
                    'done_on' => null,
                    'chase_notes' => null,
                    'next_chase_date' => null,
                    'locked' => false,
                    'lockedReason' => null,
                    'connectedTerms' => [],
                ];
            }

            $this->items = array_merge($lockedItems, $newStages);

            $this->dispatch('toast', [
                'msg' => 'Preserved ' . count($lockedItems) . ' locked milestone(s) connected to payment schedule.',
                'type' => 'ok',
            ]);
        }

        $this->resetValidation();
    }*/

    public function addRow(): void
    {
        $this->items[] = [
            'id' => null,
            'name' => '',
            'dueDate' => '',
            'status' => 'Pending',
            'done' => false,
            'done_on' => null,
            'chase_notes' => null,
            'next_chase_date' => null,
            'locked' => false,
            'lockedReason' => null,
            'connectedTerms' => [],
        ];
    }

    public function removeRow(int $index): void
    {
        if (isset($this->items[$index])) {
            if (! empty($this->items[$index]['locked'])) {
                $this->addError("items.{$index}.locked", 'Cannot delete a milestone connected to a payment schedule.');
                $this->dispatch('toast', [
                    'msg' => 'Cannot remove milestone connected to a payment schedule.',
                    'type' => 'warning',
                ]);

                return;
            }

            if (count($this->items) <= 1) {
                $this->addError('items', 'At least one milestone row must be retained.');
                $this->dispatch('toast', [
                    'msg' => 'At least one milestone row must be retained.',
                    'type' => 'warning',
                ]);

                return;
            }

            unset($this->items[$index]);
            $this->items = array_values($this->items);
            $this->resetValidation();
        }
    }

    protected function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:160',
            'items.*.dueDate' => 'nullable|date',
            'items.*.status' => 'required|in:Pending,In Progress,Done,Blocked',
            'items.*.done' => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'items.required' => 'At least one milestone row must be retained.',
            'items.min' => 'At least one milestone row must be retained.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'items.*.name' => 'milestone name',
            'items.*.dueDate' => 'due date',
            'items.*.status' => 'status',
        ];
    }

    public function updated(string $propertyName): void
    {
        // Automatically sync checkbox status with dropdown
        if (str_ends_with($propertyName, '.done')) {
            $parts = explode('.', $propertyName);
            $idx = $parts[1] ?? null;
            if ($idx !== null && isset($this->items[$idx])) {
                $this->items[$idx]['status'] = $this->items[$idx]['done'] ? 'Done' : 'Pending';
            }
        } elseif (str_ends_with($propertyName, '.status')) {
            $parts = explode('.', $propertyName);
            $idx = $parts[1] ?? null;
            if ($idx !== null && isset($this->items[$idx])) {
                $this->items[$idx]['done'] = ($this->items[$idx]['status'] === 'Done');
            }
        }

        $this->validateOnly($propertyName);
    }

    public function save(): void
    {
        if (empty($this->items)) {
            $this->addError('items', 'At least one milestone row must be retained.');
            $this->dispatch('toast', [
                'msg' => 'At least one milestone row must be retained.',
                'type' => 'warning',
            ]);

            return;
        }

        // Normalize empty string dates to null before validation
        foreach ($this->items as $index => $milestoneItem) {
            $this->items[$index]['dueDate'] = ! empty($milestoneItem['dueDate']) ? $milestoneItem['dueDate'] : null;
            $this->items[$index]['name'] = trim($milestoneItem['name'] ?? '');
            if (array_key_exists('next_chase_date', $milestoneItem)) {
                $this->items[$index]['next_chase_date'] = ! empty($milestoneItem['next_chase_date']) ? $milestoneItem['next_chase_date'] : null;
            }
            if (array_key_exists('chase_notes', $milestoneItem)) {
                $this->items[$index]['chase_notes'] = ! empty($milestoneItem['chase_notes']) ? trim($milestoneItem['chase_notes']) : null;
            }
        }

        $this->validate();

        $project = Project::findOrFail($this->projectId);
        $existingMilestones = Milestone::where('project_id', $this->projectId)->get()->keyBy('id');
        $lockedMilestoneIds = PaymentTerm::where(function ($termQuery) {
            $termQuery->where('project_id', $this->projectId)
                ->orWhereIn('milestone_id', Milestone::where('project_id', $this->projectId)->pluck('id'));
        })
            ->whereNotNull('milestone_id')
            ->pluck('milestone_id')
            ->unique()
            ->toArray();

        $seenIds = [];
        $diffs = [];

        foreach ($this->items as $index => $milestoneItem) {
            $done = ! empty($milestoneItem['done']) || ($milestoneItem['status'] === 'Done');
            $status = $done ? 'Done' : ($milestoneItem['status'] ?? 'Pending');

            $doneOn = null;
            if ($done) {
                if (! empty($milestoneItem['done_on'])) {
                    $doneOn = $milestoneItem['done_on'];
                } elseif (! empty($milestoneItem['id']) && $existingMilestones->has($milestoneItem['id'])) {
                    $prev = $existingMilestones->get($milestoneItem['id']);
                    $doneOn = $prev->done_on ? Carbon::parse($prev->done_on)->toDateString() : now()->toDateString();
                } else {
                    $doneOn = now()->toDateString();
                }
            }

            $payload = [
                'name' => $milestoneItem['name'],
                'due_date' => $milestoneItem['dueDate'],
                'status' => $status,
                'done' => $done,
                'done_on' => $doneOn,
                'sort_order' => $index + 1,
            ];

            if (array_key_exists('chase_notes', $milestoneItem)) {
                $payload['chase_notes'] = ! empty($milestoneItem['chase_notes']) ? trim($milestoneItem['chase_notes']) : null;
            }
            if (array_key_exists('next_chase_date', $milestoneItem)) {
                $payload['next_chase_date'] = ! empty($milestoneItem['next_chase_date']) ? $milestoneItem['next_chase_date'] : null;
            }

            if (! empty($milestoneItem['id'])) {
                $milestone = $existingMilestones->get($milestoneItem['id']);
                if ($milestone) {
                    $isLocked = in_array($milestone->id, $lockedMilestoneIds);
                    if ($isLocked) {
                        $payload['name'] = $milestone->name;
                    }

                    $itemChanges = [];
                    if ($milestone->name !== $payload['name']) {
                        $itemChanges[] = "name: '{$milestone->name}' -> '{$payload['name']}'";
                    }
                    $oldDue = $milestone->due_date ? Carbon::parse($milestone->due_date)->toDateString() : null;
                    if ($oldDue !== $payload['due_date']) {
                        $itemChanges[] = 'due: '.($oldDue ?: 'none').' -> '.($payload['due_date'] ?: 'none');
                    }
                    if ($milestone->status !== $payload['status']) {
                        $itemChanges[] = "status: {$milestone->status} -> {$payload['status']}";
                    }
                    if (! empty($itemChanges)) {
                        $diffs[] = "Edited '{$milestone->name}' (".implode(', ', $itemChanges).')';
                    }
                    $milestone->update($payload);
                    $seenIds[] = $milestone->id;

                    if (! empty($payload['due_date'])) {
                        PaymentTerm::where('milestone_id', $milestone->id)
                            ->where('trigger_type', 'milestone')
                            ->where('status', '!=', 'Paid')
                            ->where('received', '<=', 0)
                            ->update(['due_date' => $payload['due_date']]);
                    }
                }
            } else {
                $created = $project->milestones()->create($payload);
                $seenIds[] = $created->id;
                $diffs[] = "Added '{$created->name}' (status: {$payload['status']})";
            }
        }

        // Clean up removed milestones that are not locked to payment schedule terms
        $removed = Milestone::where('project_id', $this->projectId)
            ->whereNotIn('id', $seenIds)
            ->whereNotIn('id', $lockedMilestoneIds)
            ->get();
        foreach ($removed as $rm) {
            $diffs[] = "Removed '{$rm->name}'";
            $rm->delete();
        }

        $detailText = ! empty($diffs)
            ? "Updated milestones for {$project->name}: ".implode('; ', $diffs)
            : "Updated milestones for project {$project->name}";

        $auditChanges = [];
        foreach ($diffs as $diffItem) {
            $auditChanges[] = ['change' => $diffItem];
        }

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Updated Milestones',
            'Project',
            $project->id,
            $project->code,
            $detailText,
            ! empty($auditChanges) ? $auditChanges : null
        );

        // Notify coordinator, tech person, sales, uplines, and admins if any milestone marked Done
        $completedAny = false;
        foreach ($this->items as $milestoneItem) {
            if (! empty($milestoneItem['done']) || ($milestoneItem['status'] ?? '') === 'Done') {
                $prevDone = ! empty($milestoneItem['id']) && $existingMilestones->has($milestoneItem['id']) && $existingMilestones->get($milestoneItem['id'])->done;
                if (! $prevDone) {
                    $completedAny = true;
                    app(AuditNotificationService::class)->notifyProjectStakeholders(
                        $project,
                        'Milestone Completed',
                        "Milestone Done: {$milestoneItem['name']}",
                        "Milestone '{$milestoneItem['name']}' on project {$project->name} was marked as completed by ".auth()->user()->name.'.',
                        'project',
                        $project->id
                    );
                }
            }
        }

        if (! $completedAny && ! empty($diffs)) {
            app(AuditNotificationService::class)->notifyProjectStakeholders(
                $project,
                'Milestones Updated',
                "Milestones Updated: {$project->name}",
                "Milestones for project {$project->name} ({$project->code}) were updated by ".auth()->user()->name.'.',
                'project',
                $project->id
            );
        }

        $this->dispatch('close-modal', 'project-milestone-modal');
        $this->dispatch('project-saved');
        $this->dispatch('toast', ['msg' => 'Milestones updated successfully', 'type' => 'ok']);
    }

    public function render()
    {
        return view('livewire.projects.project-milestone-modal');
    }
}
