<?php

namespace Plum\SeleniumIdeManager\Http\Controllers;

use Plum\SeleniumIdeManager\Models\Suite;

class SyncController extends BaseController
{
    /**
     * @var Suite
     */
    private $suite;

    private $spreadsheetId;

    /**
     * @var \Google_Service_Sheets
     */
    private $service = null;

    public function __construct(Suite $suite)
    {
        parent::__construct();
        $this->suite = $suite;
        $this->spreadsheetId = config('selenium_ide_manager.google_spreadsheets_id');
    }

    public function index()
    {
        $result = $this->suite->getAllSuite();
        if (empty($result)) {
            return redirect()->route('selenium-ide-manager.suite.index')->with('danger', 'Data not found');
        }

        $sheetData = [];
        $listSuite = [];
        foreach ($result as $suite) {
            $suiteName = $suite['name'];
            if (empty($suite['test_cases'])) {
                continue;
            }

            $firstTestCase = '';
            $totalTestCase = 0;
            foreach ($suite['test_cases'] as $testCase) {
                if (empty($testCase['commands'])) {
                    continue;
                }

                $totalTestCase++;
                $commands = [];
                foreach ($testCase['commands'] as $item) {
                    $command = $item['command'];
                    $target = $item['target'];
                    if ($command == 'run') {
                        $target = $suiteName . '_' . $target;
                    }
                    $commands[] = [
                        $command,
                        $target,
                        $item['value']
                    ];
                }
                $testCaseName = $suiteName . '_' . $testCase['name'];
                $sheetData[] = [
                    'name' => $testCaseName,
                    'commands' => $commands,
                    'tabColor' => $this->hexToRGB($suite['hex_color'])
                ];

                if ($testCase['first_test_case'] == 1) {
                    $firstTestCase = $testCaseName;
                }
            }

            $listSuite[] = [
                $suiteName,
                $firstTestCase,
                $totalTestCase,
                $suite['status'] == 1 ? 'Enabled' : 'Disabled'
            ];
        }

        if (empty($sheetData)) {
            return redirect()->route('selenium-ide-manager.suite.index')->with('danger', 'Data not found');
        }

        $client = new \Google_Client();
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . config('selenium_ide_manager.google_application_credentials'));
        $client->useApplicationDefaultCredentials();
        $client->addScope(\Google_Service_Drive::DRIVE);
        $this->service = new \Google_Service_Sheets($client);

        $listSheet = [];
        $sheets = $this->service->spreadsheets->get($this->spreadsheetId)->getSheets();

        $listSheetId = [];
        foreach ($sheets as $s) {
            $title = $s->getProperties()->getTitle();
            $listSheet[] = $title;
            $listSheetId[$title] = $s->getProperties()->getSheetId();
        }

        $this->clearDataGoogleSpreadsheets('Test Follow!B2:E');
        $this->updateGoogleSheet('Test Follow!B2', $listSuite);

        foreach ($sheetData as $item) {
            $sheetTitle = $item['name'];
            if (in_array($sheetTitle, $listSheet)) {
                $this->clearDataGoogleSpreadsheets($item['name'] . '!B2:D');

                $this->updateTabColor($listSheetId[$sheetTitle], $item['tabColor']);
            } else {
                $this->createNewSheet($item['name'], $item['tabColor']);
            }
            $this->updateGoogleSheet($sheetTitle . '!B2', $item['commands']);
        }

        return redirect()->route('selenium-ide-manager.suite.index')->with(
            'success',
            'Sync data to google spreadsheets success'
        );
    }

    private function updateTabColor($sheetId, $tabColor)
    {
        $requests = [
            new \Google_Service_Sheets_Request([
                'updateSheetProperties' => [
                    'properties' => [
                        'sheetId' => $sheetId,
                        'tabColor' => $tabColor
                    ],
                    'fields' => 'tabColor'
                ]
            ])
        ];

        $batchUpdateRequest = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            'requests' => $requests
        ]);
        $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdateRequest);
    }

    private function clearDataGoogleSpreadsheets($sheetTitle)
    {
        $clearRange = $sheetTitle;
        $clearBody = new \Google_Service_Sheets_ClearValuesRequest();

        $this->service->spreadsheets_values->clear(
            $this->spreadsheetId,
            $clearRange,
            $clearBody
        );

    }

    private function hexToRGB($hex)
    {
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;
        return [
            'red' => $r,
            'green' => $g,
            'blue' => $b,
            'alpha' => 1
        ];
    }

    /**
     * Created by ManNV
     * @param $sheetTitle
     * @return \Google_Service_Sheets_SheetProperties
     */
    private function createNewSheet($sheetTitle, $tabColor)
    {
        $requestBody = new \Google_Service_Sheets_CopySheetToAnotherSpreadsheetRequest();
        $requestBody->setDestinationSpreadsheetId($this->spreadsheetId);
        $testCaseTemplateId = config('selenium_ide_manager.test_case_sheet_id');
        $newSheet = $this->service->spreadsheets_sheets->copyTo(
            $this->spreadsheetId,
            $testCaseTemplateId,
            $requestBody
        );

        $requests = [
            new \Google_Service_Sheets_Request([
                'updateSheetProperties' => [
                    'properties' => [
                        'sheetId' => $newSheet->getSheetId(),
                        'title' => $sheetTitle,
                    ],
                    'fields' => 'title'
                ]
            ]),
            new \Google_Service_Sheets_Request([
                'updateSheetProperties' => [
                    'properties' => [
                        'sheetId' => $newSheet->getSheetId(),
                        'tabColor' => $tabColor
                    ],
                    'fields' => 'tabColor'
                ]
            ])
        ];

        $batchUpdateRequest = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            'requests' => $requests
        ]);
        $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdateRequest);
        return $newSheet;
    }

    private function updateGoogleSheet($range, $data)
    {
        $updateRange = $range;
        $updateBody = new \Google_Service_Sheets_ValueRange();
        $updateBody->setRange($updateRange);
        $updateBody->setMajorDimension('ROWS');
        $updateBody->setValues($data);

        $this->service->spreadsheets_values->update(
            $this->spreadsheetId,
            $updateRange,
            $updateBody,
            ['valueInputOption' => 'USER_ENTERED']
        );
    }
}
