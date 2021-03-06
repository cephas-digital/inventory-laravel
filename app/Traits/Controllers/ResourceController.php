<?php

namespace App\Traits\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\History;

trait ResourceController
{
    use ResourceHelper;

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //$this->authorize('viewList', $this->getResourceModel());
        if(!Auth::user()->hakAkses($this->hakAkses['index'])){
            $this->authorize('forceFail');
        }
        $paginatorData = [];
        $perPage = (int) $request->input('per_page', '');
        $perPage = (is_numeric($perPage) && $perPage > 0 && $perPage <= 100) ? $perPage : 15;
        if ($perPage != 15) {
            $paginatorData['per_page'] = $perPage;
        }
        $search = trim($request->input('search', ''));
        if (! empty($search)) {
            $paginatorData['search'] = $search;
        }
        $records = $this->getSearchRecords($request, $perPage, $search);
        $records->appends($paginatorData);

        $pkey='id';
        if(isset($this->primaryKey)){
            $pkey=$this->primaryKey;
        }
        return view($this->filterIndexView('_resources.index'), $this->filterSearchViewData($request, [
            'primaryKey' => $pkey,
            'records' => $records,
            'search' => $search,
            'resourceAlias' => $this->getResourceAlias(),
            'resourceRoutesAlias' => $this->getResourceRoutesAlias(),
            'resourceTitle' => $this->getResourceTitle(),
            'perPage' => $perPage,
        ]));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if(!Auth::user()->hakAkses($this->hakAkses['add'])){
            $this->authorize('forceFail');
        }
        $class = $this->getResourceModel();
        return view($this->filterCreateView('_resources.create'), $this->filterCreateViewData([
            'record' => new $class(),
            'resourceAlias' => $this->getResourceAlias(),
            'resourceRoutesAlias' => $this->getResourceRoutesAlias(),
            'resourceTitle' => $this->getResourceTitle(),
        ]));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        if(!Auth::user()->hakAkses($this->hakAkses['add'])){
            $this->authorize('forceFail');
        }

        $valuesToSave = $this->getValuesToSave($request);
        $request->merge($valuesToSave);
        $this->resourceValidate($request, 'store');

        if ($record = $this->getResourceModel()::create($this->alterValuesToSave($request, $valuesToSave))) {
            $nama=(isset($record->namabarang))?$record->namabarang:$record->nama;
            flash()->success('Element successfully inserted.');
            History::addHistory('store.'.$this->getResourceAlias(),'Menambahkan data.'.$nama);
            return $this->getRedirectAfterSave($record);
        } else {
            flash()->info('Element was not inserted.');
        }

        return $this->redirectBackTo(route($this->getResourceRoutesAlias().'.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return redirect(route($this->getResourceRoutesAlias().'.edit', $id));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit($id)
    {
        $record = $this->getResourceModel()::findOrFail($id);

        if(!Auth::user()->hakAkses($this->hakAkses['edit'])){
            $this->authorize('forceFail');
        }
        $pkey='id';
        if(isset($this->primaryKey)){
            $pkey=$this->primaryKey;
        }
        return view($this->filterEditView('_resources.edit'), $this->filterEditViewData($record, [
            'primaryKey' => $pkey,
            'record' => $record,
            'resourceAlias' => $this->getResourceAlias(),
            'resourceRoutesAlias' => $this->getResourceRoutesAlias(),
            'resourceTitle' => $this->getResourceTitle(),
        ]));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $id)
    {
        $record = $this->getResourceModel()::findOrFail($id);

        if(!Auth::user()->hakAkses($this->hakAkses['edit'])){
            $this->authorize('forceFail');
        }

        $valuesToSave = $this->getValuesToSave($request, $record);
        $request->merge($valuesToSave);
        $this->resourceValidate($request, 'update', $record);

        if ($record->update($this->alterValuesToSave($request, $valuesToSave))) {
            flash()->success('Element successfully updated.');

            return $this->getRedirectAfterSave($record);
        } else {
            flash()->info('Element was not updated.');
        }

        return $this->redirectBackTo(route($this->getResourceRoutesAlias().'.index'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy($id)
    {
        $record = $this->getResourceModel()::findOrFail($id);

        if(!Auth::user()->hakAkses($this->hakAkses['delete'])){
            $this->authorize('forceFail');
        }

        if (! $this->checkDestroy($record)) {
            return redirect(route($this->getResourceRoutesAlias().'.index'));
        }

        if ($record->delete()) {
            flash()->success('Element successfully deleted.');
        } else {
            flash()->info('Element was not deleted.');
        }

        return $this->redirectBackTo(route($this->getResourceRoutesAlias().'.index'));
    }
}
