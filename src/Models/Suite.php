<?php

namespace Plum\SeleniumIdeManager\Models;

class Suite extends BaseModel
{
    protected $table = 'sle_suites';

    protected $fillable = ['name', 'ide_file_path', 'description', 'status', 'hex_color'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function testCases()
    {
        return $this->hasMany(TestCase::class, 'suite_id', 'id');
    }

    public function createNewSuite($name, $path = null)
    {
        $suite = new static();
        $suite->name = $name;
        $suite->ide_file_path = $path ?? 'N/A';
        $suite->status = true;
        $suite->save();

        return $suite;
    }

    public function getSuiteById($id, $relations = [])
    {
        if (!empty($relations)) {
            return $this->with($relations)->findOrFail($id);
        }
        return $this->findOrFail($id);
    }

    public function deleteById($id)
    {
        TestCase::where(['suite_id' => $id])->delete();
        Command::where(['suite_id' => $id])->delete();
        return $this->find($id)->delete();
    }

    public function getAll()
    {
        return $this->with(['testCases'])->get()->toArray();
    }

    public function changeStatus($id, $status)
    {
        $this->find($id)->update(['status' => !$status]);
    }

    public function changeColor($id, $color)
    {
        $this->find($id)->update(['hex_color' => $color]);
    }

    public function getAllSuite()
    {
        return $this->with([
            'testCases' => function ($query) {
                return $query->with(['commands']);
            }
        ])->get()->toArray();
    }
}
