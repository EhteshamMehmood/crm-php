<?php
/* Copyright (C) 2012	Regis Houssin	<regis@dolibarr.fr>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

$exports = array(
		'CHARSET' => 'UTF-8',
		'ExportsArea' => '出口地区',
		'ImportArea' => '进口地区',
		'NewExport' => '新的出口',
		'NewImport' => '新的进口',
		'ExportableDatas' => '导出的数据集',
		'ImportableDatas' => '导入数据集',
		'SelectExportDataSet' => '选择您要导出数据集...',
		'SelectImportDataSet' => '选择要导入的数据集...',
		'SelectExportFields' => '选择您要导出字段，或选择一个预定义的出口材',
		'SelectImportFields' => '选择源文件要导入的字段在数据库领域的目标通过移动向上和向下与％s的锚，或选择一个预定义的进口材：',
		'NotImportedFields' => '田源文件不导入',
		'SaveExportModel' => '保存这个导出配置，如果你打算以后再用...',
		'SaveImportModel' => '保存这个导入配置文件，如果你打算以后再用...',
		'ExportModelName' => '导出配置文件的名称',
		'ExportModelSaved' => '导出配置文件<b>％</b>保存在<b>所属</b>期。',
		'ExportableFields' => '出口领域',
		'ExportedFields' => '出口领域',
		'ImportModelName' => '导入配置文件的名称',
		'ImportModelSaved' => '导入配置文件<b>％</b>保存在<b>所属</b>期。',
		'ImportableFields' => '导入字段',
		'ImportedFields' => '导入的字段',
		'DatasetToExport' => '出口数据集',
		'DatasetToImport' => '导入文件到数据集',
		'NoDiscardedFields' => '在源文件中没有字段丢弃',
		'Dataset' => '数据集',
		'ChooseFieldsOrdersAndTitle' => '选择字段的顺序...',
		'FieldsOrder' => '字段的顺序',
		'FieldsTitle' => '字段标题',
		'FieldOrder' => '场秩序',
		'FieldTitle' => '字段标题',
		'ChooseExportFormat' => '选择导出格式',
		'NowClickToGenerateToBuildExportFile' => '现在，组合框，然后点击选择文件格式“生成”，以建立出口文件...',
		'AvailableFormats' => '可用的格式',
		'LibraryShort' => '图书馆',
		'LibraryUsed' => '图书馆使用',
		'LibraryVersion' => '版本',
		'Step' => '一步',
		'FormatedImport' => '导入助手',
		'FormatedImportDesc1' => '此区域允许进口的个性化数据，使用过程中的助手，帮助您没有技术知识。',
		'FormatedImportDesc2' => '第一步是选择一个数据国王要加载，然后文件加载，然后选择哪些字段要加载。',
		'FormatedExport' => '出口助理',
		'FormatedExportDesc1' => '此区域允许出口的个性化数据，使用过程中的助手，帮助您没有技术知识。',
		'FormatedExportDesc2' => '第一步是选择一个预定义的数据集，然后选择在哪些领域你的结果文件你想要的，和秩序。',
		'FormatedExportDesc3' => '当数据出口都被选中，你可以定义输出文件格式您要导出您的数据。',
		'Sheet' => '片',
		'NoImportableData' => '没有导入数据（没有定义模块，让数据导入）',
		'FileSuccessfullyBuilt' => 'Export file generated',
		'SQLUsedForExport' => 'SQL Request used to build export file',
		'LineId' => 'Id of line',
		'LineDescription' => '说明线',
		'LineUnitPrice' => '优惠价线',
		'LineVATRate' => '增值税率线',
		'LineQty' => '线路数量',
		'LineTotalHT' => '额扣除税线',
		'LineTotalTTC' => '税收总额为线',
		'LineTotalVAT' => '增值税额的线路',
		'TypeOfLineServiceOrProduct' => '型线（0 =产品，1 =服务）',
		'FileWithDataToImport' => '与数据文件导入',
		'FileToImport' => '源文件导入',
		'FileMustHaveOneOfFollowingFormat' => '要导入的文件必须具有以下格式之一',
		'DownloadEmptyExample' => '下载源文件的例子空',
		'ChooseFormatOfFileToImport' => '选择文件格式为导入文件格式使用的象形％s到点击选择它...',
		'ChooseFileToImport' => '上传文件然后点击象形％s到选择导入文件作为源文件...',
		'SourceFileFormat' => '源文件格式',
		'FieldsInSourceFile' => '在源文件中的字段',
		'FieldsInTargetDatabase' => '在Dolibarr数据库（*=强制性目标字段）',
		'Field' => '场',
		'NoFields' => '没有字段',
		'MoveField' => '移动领域的％s的列号',
		'ExampleOfImportFile' => 'Example_of_import_file',
		'SaveImportProfile' => '保存这个导入配置文件',
		'ErrorImportDuplicateProfil' => '无法保存此配置文件导入这个名字。现有的配置文件已经存在具有此名称。',
		'ImportSummary' => '导入安装摘要',
		'TablesTarget' => '有针对性的表',
		'FieldsTarget' => '有针对性的领域',
		'TableTarget' => '有针对性的表',
		'FieldTarget' => '有针对性的领域',
		'FieldSource' => '水源地',
		'DoNotImportFirstLine' => '不要进口源文件的第一行',
		'NbOfSourceLines' => '在源文件的行数',
		'NowClickToTestTheImport' => '检查输入你所定义的参数。如果他们是正确的，按一下按钮<b>％</b>的<b>“S”</b>来启动数据库的导入过程的模拟（无数据将在你改变，这只是一个模拟的时刻）...',
		'RunSimulateImportFile' => '启动进口仿真',
		'FieldNeedSource' => '这种感觉需要从源数据库中的数据文件',
		'SomeMandatoryFieldHaveNoSource' => '有些领域没有强制性的从数据源文件',
		'InformationOnSourceFile' => '关于源信息文件',
		'InformationOnTargetTables' => '在信息领域的目标',
		'SelectAtLeastOneField' => '开关至少一源的字段列字段出口',
		'SelectFormat' => '选择此导入文件格式',
		'RunImportFile' => '启动导入文件',
		'NowClickToRunTheImport' => '检查进口仿真结果。如果一切正常，启动最终进口。',
		'DataLoadedWithId' => '所有数据都将被载入与下面的导入编号<b>：％s的</b>',
		'ErrorMissingMandatoryValue' => '强制性数据是<b>％</b>空场源文件中<b>的</b> S。',
		'TooMuchErrors' => '还有<b>％的台词</b> ，但有错误的其他来源，但产量一直有限。',
		'TooMuchWarnings' => '还有<b>％s的</b>线，警告其他来源，但产量一直有限。',
		'EmptyLine' => '空行（将被丢弃）',
		'CorrectErrorBeforeRunningImport' => '您必须先输入正确运行前确定的所有错误。',
		'FileWasImported' => '进口数量<b>%s</b>文件。',
		'YouCanUseImportIdToFindRecord' => '你可以找到所有进口领域<b>import_key</b>记录过滤您的数据库<b>=\'％s\'的</b> 。',
		'NbOfLinesOK' => '行数没有错误，也没有警告<b>：％s的</b> 。',
		'NbOfLinesImported' => '线成功导入数<b>：％s的</b> 。',
		'DataComeFromNoWhere' => '值插入来自无处源文件。',
		'DataComeFromFileFieldNb' => '值插入来自<b>S</b>的源文件<b>％</b>来自外地的数目。',
		'DataComeFromIdFoundFromRef' => '值<b>％</b>来自外地号码文件<b>的</b> S来源将被用来找到父对象的ID使用（因此，客体<b>％s的</b>具有参考。Dolibarr从源文件必须存在到）。',
		'DataComeFromIdFoundFromCodeId' => '代码将用于从源文件中的字段数<b>%s</b>找到父对象的ID使用（因此，从源文件中的代码必须存在dictionnary <b>%s）。</b>请注意，如果你知道ID，你也可以使用到源文件，而不是代码。进口应该在这两种情况下工作。',
		'DataIsInsertedInto' => '未来的数据源文件将被插入到以下领域：',
		'DataIDSourceIsInsertedInto' => '标识对象的家长发现使用源文件中的数据，将被插入到下面的字段：',
		'DataCodeIDSourceIsInsertedInto' => 'ID从父行代码中发现，将被插入到下面的字段：',
		'SourceRequired' => '数据值是强制性的',
		'SourceExample' => '例如可能的数据值',
		'ExampleAnyRefFoundIntoElement' => '任何ref元素<b>%s</b>',
		'ExampleAnyCodeOrIdFoundIntoDictionnary' => '发现任何代码（或ID）dictionnary <b>%s</b>',
		'CSVFormatDesc' => '<b>逗号分隔值</b>文件格式（。csv格式）。 <br>这是一个文本文件格式字段被分隔在[%s]分开。如果一个字段分隔符是里面的内容发现，现场是圆形的圆字[%s]。字符转义字符是为了逃避轮[%s]。',
		'Excel95FormatDesc' => '<b>Excel</b> file format (.xls)<br>This is native Excel 95 format (BIFF5).',
		'Excel2007FormatDesc' => '<b>Excel</b> file format (.xlsx)<br>This is native Excel 2007 format (SpreadsheetML).',
		'TsvFormatDesc' => '<b>Tab Separated Value</b> file format (.tsv)<br>This is a text file format where fields are separated by a tabulator [tab].',
		'ExportFieldAutomaticallyAdded' => 'Field <b>%s</b> was automatically added. It will avoid you to have similar lines to be treated as duplicate records (with this field added, all ligne will own its own id and will differ).',
		'CsvOptions' => 'Csv Options',
		'Separator' => 'Separator',
		'Enclosure' => 'Enclosure',
		'SuppliersProducts' => 'Suppliers Products'
);
?>