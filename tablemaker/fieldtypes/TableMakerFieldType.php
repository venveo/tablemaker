<?php
namespace Craft;

/**
 * TableMaker by Supercool
 *
 * This bears an un-canny resemblence to the normal Table FieldType.
 * The code is mostly the same, just twiddled with a bit.
 *
 * These options should be added to the columns table:
 * - width
 * - alignment
 *
 * There are no settings right now - one day we might add some that
 * let you set max cols / rows or somesuch excitement.
 *
 * @package	 	TableMaker
 * @author		Josh Angell
 * @copyright Copyright (c) 2014, Supercool Ltd
 * @link			http://www.supercooldesign.co.uk
 */

class TableMakerFieldType extends BaseFieldType
{

	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IComponentType::getName()
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Table Maker');
	}

	/**
	 * @inheritDoc IFieldType::defineContentAttribute()
	 *
	 * @return mixed
	 */
	public function defineContentAttribute()
	{
		return AttributeType::Mixed;
	}

	/**
	 * @inheritDoc ISavableComponentType::getSettingsHtml()
	 *
	 * @return string|null
	 */
	public function getSettingsHtml()
	{

		return craft()->templates->render('tablemaker/settings', array(
			'settings' => $this->getSettings()
		));

	}

	/**
	 * @inheritDoc IFieldType::getInputHtml()
	 *
	 * @param string $name
	 * @param mixed	$value
	 *
	 * @return string
	 */
	public function getInputHtml($name, $value)
	{

		// make input
		$input = '<input class="table-maker-field" type="hidden" name="'.$name.'" value="">';


		// get columns from db or fall back to default
		if ( ! empty($value['columns']) )
		{

			foreach ($value['columns'] as $key => $val) {

				$columns['col'.$key] = array(
					'heading' => $val['heading'],
					'type' => 'singleline'
				);

			}

		}
		else
		{

			$columns = array('col1' => array('heading' => '', 'type' => 'singleline'));

		}


		// get rows from db or fall back to default
		if ( ! empty($value['rows']) )
		{

			// walk down the rows and cells appending 'row' to the rows' keys
			// and 'col' to the cells' keys
			foreach ($value['rows'] as $rowKey => $rowVal) {

				foreach ($rowVal as $colKey => $colVal) {

					$rows['row'.$rowKey]['col'.$colKey] = $colVal;

				}

			}

		}
		else
		{

			$rows = array('row1' => array());

		}


		// prep col settings
		$columnSettings = array(
			'heading' => array(
				'heading' => Craft::t('Column Heading'),
				'type' => 'singleline'
			)
		);



		// set up top table to add to bottom table

		craft()->templates->includeJsResource('tablemaker/js/tablemaker.js');

		craft()->templates->includeJs('new Craft.TableMaker(' .
			'"'.craft()->templates->namespaceInputId($name).'", ' .
			'"'.craft()->templates->namespaceInputId('columns').'", ' .
			'"'.craft()->templates->namespaceInputId('rows').'", ' .
			'"'.craft()->templates->namespaceInputName('columns').'", ' .
			'"'.craft()->templates->namespaceInputName('rows').'", ' .
			JsonHelper::encode($columns).', ' .
			JsonHelper::encode($rows).', ' .
			JsonHelper::encode($columnSettings) .
		');');

		$fieldSettings = $this->getSettings();

		$columnsField = craft()->templates->renderMacro('_includes/forms', 'editableTableField', array(
			array(
				'label'					=> $fieldSettings['columnsLabel'] ? Craft::t($fieldSettings['columnsLabel']) : Craft::t('Table Columns'),
				'instructions'	=> $fieldSettings['columnsInstructions'] ? Craft::t($fieldSettings['columnsInstructions']) : Craft::t('Define the columns your table should have.'),
				'id'						=> 'columns',
				'name'					=> 'columns',
				'cols'					=> $columnSettings,
				'rows'					=> $columns,
				'addRowLabel'		=> $fieldSettings['columnsAddRowLabel'] ? Craft::t($fieldSettings['columnsAddRowLabel']) : Craft::t('Add a column'),
				'initJs'				=> false
			)
		));

		$rowsField = craft()->templates->renderMacro('_includes/forms', 'editableTableField', array(
			array(
				'label'					=> $fieldSettings['rowsLabel'] ? Craft::t($fieldSettings['rowsLabel']) : Craft::t('Table Content'),
				'instructions'	=> $fieldSettings['rowsInstructions'] ? Craft::t($fieldSettings['rowsInstructions']) : Craft::t('Input the content of your table.'),
				'id'						=> 'rows',
				'name'					=> 'rows',
				'cols'					=> $columns,
				'rows'					=> $rows,
				'addRowLabel'		=> $fieldSettings['rowsAddRowLabel'] ? Craft::t($fieldSettings['rowsAddRowLabel']) : Craft::t('Add a row'),
				'initJs'				=> false
			)
		));

		return $input . $columnsField . $rowsField;

	}

	/**
	 * @inheritDoc IFieldType::prepValueFromPost()
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValueFromPost($value)
	{

		$value = JsonHelper::decode($value);

		if ( is_array($value['rows']) )
		{

			// drop keys from the rows array
			$value['rows'] = array_values($value['rows']);

			// drop each rows content array keys
			foreach ($value['rows'] as &$rowArray)
			{

				if ( is_array($rowArray) )
				{

					$rowArray = array_values($rowArray);

				}

			}

		}


		// drop keys from the columns array
		if ( is_array($value['columns']) )
		{

			$value['columns'] = array_values($value['columns']);

		}

		return JsonHelper::encode($value);

	}

	/**
	 * @inheritDoc IFieldType::prepValue()
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValue($value)
	{

		// make an html table

		$html = '
			<table>

				<thead>
					<tr>
		';

						foreach ($value['columns'] as $col)
						{
							$html .= '<th>' . $col['heading'] . '</th>';
						}

		$html .= '
					</tr>
				</thead>

				<tbody>

		';

				foreach ($value['rows'] as $row)
				{

					$html .= '<tr>';

					foreach ($row as $cell) {
						$html .= '<td>' . $cell . '</td>';
					}

					$html .= '</tr>';

				}

		$html .= '

				</tbody>

			</table>
		';

		return TemplateHelper::getRaw($html);

	}


	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseSavableComponentType::defineSettings()
	 *
	 * @return array
	 */
	protected function defineSettings()
	{
		return array(
			'columnsLabel' => AttributeType::String,
			'columnsInstructions' => AttributeType::String,
			'columnsAddRowLabel' => AttributeType::String,
			'rowsLabel' => AttributeType::String,
			'rowsInstructions' => AttributeType::String,
			'rowsAddRowLabel' => AttributeType::String
		);
	}


}
