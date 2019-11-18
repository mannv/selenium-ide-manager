<?php

namespace Plum\SeleniumIdeManager\Http\Controllers;

use Illuminate\Support\Str;
use Plum\SeleniumIdeManager\Models\Command;
use Plum\SeleniumIdeManager\Models\Suite;
use Plum\SeleniumIdeManager\Models\TestCase;

class IndexController extends BaseController
{
    /**
     * @var Suite
     */
    private $suite;

    /**
     * @var TestCase
     */
    private $testCase;

    /**
     * @var Command
     */
    private $command;

    public function __construct(Suite $suite, TestCase $testCase, Command $command)
    {
        parent::__construct();
        $this->suite = $suite;
        $this->testCase = $testCase;
        $this->command = $command;
    }

    public function index()
    {
        $result = $this->suite->getAll();
        return view('seleniumidemanager::index', ['data' => $result]);
    }

    public function create()
    {
        return view('seleniumidemanager::create');
    }

    private function storageSiteFile()
    {
        $file = $this->request->file('file');
        $fileName = 'selenium/' . uniqid() . '.' . $file->getClientOriginalExtension();
        $driver = config('selenium_ide_manager.storage');
        \Storage::disk($driver)->put($fileName, \File::get($file), 'public');
        return \Storage::disk($driver)->url($fileName);
    }

    public function store()
    {
        if (!$this->request->hasFile('file')) {
            return redirect()->route('selenium-ide-manager.suite.index')->with('danger', 'Please choose file .site');
        }

        $filePath = $this->storageSiteFile();

        $jsonText = \File::get($this->request->file('file'));
        $json = json_decode($jsonText, true);

        try {
            \DB::beginTransaction();

            $suiteName = $json['suites'][0]['name'];
            $suite = $this->suite->createNewSuite($suiteName, $filePath);


            if (!empty($json['tests'])) {
                foreach ($json['tests'] as $test) {
                    $testCaste = $this->testCase->createNewTestCase($suite->id, $test['name']);

                    if (!empty($test['commands'])) {
                        foreach ($test['commands'] as $index => $item) {
                            $this->command->createNewCommand([
                                'suite_id' => $suite->id,
                                'test_case_id' => $testCaste->id,
                                'comment' => $item['comment'],
                                'command' => $item['command'],
                                'target' => $item['target'],
                                'value' => $item['value'],
                                'targets' => json_encode($item['targets']),
                                'weight' => $index
                            ]);
                        }
                    }
                }
            }


            \DB::commit();
        } catch (\Exception $exception) {
            \DB::rollBack();
            app('log')->error($exception);
        }
        return redirect()->route('selenium-ide-manager.suite.index')->with('success', 'Import suite success');
    }

    private function deleteFileSite($url)
    {
        $driver = config('selenium_ide_manager.storage');
        $storage = \Storage::disk($driver);
        $storageUrl = $storage->getDriver()->getConfig()->get('url');
        $path = Str::replaceFirst($storageUrl, '', $url);
        \Storage::disk($driver)->delete($path);
    }

    public function destroy($id)
    {
        $suite = $this->suite->getSuiteById($id);
        $this->deleteFileSite($suite->ide_file_path);
        $suite->deleteById($id);
        return redirect()->route('selenium-ide-manager.suite.index')->with('success', 'Delete suite success');
    }

    public function changeStatus($id)
    {
        $suite = $this->suite->getSuiteById($id);
        return $this->suite->changeStatus($id, $suite->status);
    }

    public function changeColor()
    {
        $suiteId = $this->request->get('id');
        $color = $this->request->get('hex_color');
        return $this->suite->changeColor($suiteId, $color);
    }
}
