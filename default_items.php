<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_tags
 *
 * @copyright   Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * modifyed crock@vodafone.de
 * September 2018
 */

defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers');

// Get the user object.
$user = JFactory::getUser();

// Check if user is allowed to add/edit based on tags permissions.
$canEdit = $user->authorise('core.edit', 'com_tags');
$canCreate = $user->authorise('core.create', 'com_tags');
$canEditState = $user->authorise('core.edit.state', 'com_tags');

$columns = $this->params->get('tag_columns', 1);
// Avoid division by 0 and negative columns.
if ($columns < 1)
{
	$columns = 1;
}
$bsspans = floor(12 / $columns);
if ($bsspans < 1)
{
	$bsspans = 1;
}

$bscolumns = min($columns, floor(12 / $bsspans));
$n = count($this->items);

// ==================== get new items ===== Crock==
function getNewItems($params){  
		//== get params
		$published      = $params->get('published', 1);
		$orderDirection = $params->get('all_tags_orderby_direction', 'ASC');
		$language       = $params->get('tag_list_language_filter', 'all');
	
		//== Create a new query object.
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
	

		//== Select the required fields from the table.
		$query->select('a.*');
		$query->from('#__tags AS a')
			->where('a.alias <> ' . $db->quote('root'));
	
		// Join over the language
		if ($language !== 'all')
		{
			if ($language === 'current_language')
			{
				$language = JHelperContent::getCurrentLanguage();
			}

			$query->where($db->quoteName('language') . ' IN (' . $db->quote($language) . ', ' . $db->quote('*') . ')');
		}


		//== Filter by published state

		if (is_numeric($published))
		{
			$query->where('a.published = ' . (int) $published);
		}
		elseif ($published === '')
		{
			$query->where('(a.published IN (0, 1))');
		}

		//== ordering
		$listOrdering = 'a.access'; 
		$listDirn = $db->escape($orderDirection);

		if ($listOrdering == 'a.access')
		{
			$query->order('a.access ' . $listDirn . ', a.lft ' . $listDirn);
		}
		else
		{
			$query->order($db->escape($listOrdering) . ' ' . $listDirn);
		}

	
		$db->setQuery($query);
		// ==Load the results as a list of stdClass objects (see later for more options on retrieving data).
		$results = $db->loadObjectList(); 
		return $results; 
	
	}

// ==================================
?>

<form action="<?php echo htmlspecialchars(JUri::getInstance()->toString()); ?>" method="post" name="adminForm" id="adminForm">
	<?php if ($this->params->get('filter_field') != '0' || $this->params->get('show_pagination_limit')) : ?>
	<fieldset class="filters btn-toolbar">
		<?php if ($this->params->get('filter_field') !== '0') : ?>
			<div class="btn-group">
				<input type="text" name="filter-search" id="filter-search" value="<?php echo $this->escape($this->state->get('list.filter')); ?>" class="inputbox" onchange="document.adminForm.submit();" title="<?php echo JText::_('COM_TAGS_FILTER_SEARCH_DESC'); ?>" placeholder="<?php echo JText::_('COM_TAGS_TITLE_FILTER_LABEL'); ?>" />
			</div>
		<?php endif; ?>
		<?php if ($this->params->get('show_pagination_limit')) : ?>
			<div class="btn-group pull-right">
				
				<?php echo $this->pagination->getLimitBox(); ?>
			</div>
		<?php endif; ?>

		<input type="hidden" name="filter_order" value="" />
		<input type="hidden" name="filter_order_Dir" value="" />
		<input type="hidden" name="limitstart" value="" />
		<input type="hidden" name="task" value="" />
		<div class="clearfix"></div>
	</fieldset>
	<?php endif; ?>

<?php if ($this->items == false || $n == 0) : ?>
	<p><?php echo JText::_('COM_TAGS_NO_TAGS'); ?></p>
<?php else : 
	
	
	//================= Start create new items============================
		$app = JFactory::getApplication('site');
		$state = $app->input->getInt('parent_id', 0); // menu params
	if($state == '0'){
		$result = getNewItems($this->params);
		$this->items = $result;
	}
	// ========== END ============================================
	
	?>
	<?php foreach ($this->items as $i => $item) : ?>
		<?php if ($n == 1 || $i == 0 || $bscolumns == 1 || $i % $bscolumns == 0) : ?>
			<ul class="thumbnails blank">
		<?php endif; ?>
		<?php if ((!empty($item->access)) && in_array($item->access, $this->user->getAuthorisedViewLevels())) : ?>
 			<li class="cat-list-row<?php echo $i % 2; ?>" >
				<h3 class="page-header item-title">
					<a href="<?php echo JRoute::_(TagsHelperRoute::getTagRoute($item->id . ':' . $item->alias)); ?>">
						<?php
						//==== get level hyphen
						$flash ='';
						$lev = $item->level;
						if($lev > 1) {
						for($i=0; $i<$lev -1; $i++){
							$flash .= '-'.' ';
						}
						}
						//====
						?>
						<?php echo $flash.$this->escape($item->title); ?>
					</a>
					<?php if ($this->params->get('all_tags_show_tag_hits')) : ?>
							<span class="list-hits badge badge-info">
								<?php echo JText::sprintf('JGLOBAL_HITS_COUNT', $item->hits); ?>
							</span>
					<?php endif; ?>
				</h3>
		<?php endif; ?>
		
		
		
		<?php if ($this->params->get('all_tags_show_tag_image') && !empty($item->images)) : ?>
			<?php $images  = json_decode($item->images); ?>
					<span class="tag-image-intro">
					<?php if (!empty($images->image_intro)): ?>
						<?php $imgfloat = (empty($images->float_intro)) ? $this->params->get('float_intro') : $images->float_intro; ?>
						<div class="pull-<?php echo htmlspecialchars($imgfloat); ?> item-image">
							<img
						<?php if ($images->image_intro_caption) : ?>
							<?php echo 'class="caption"' . ' title="' . htmlspecialchars($images->image_intro_caption) . '"'; ?>
						<?php endif; ?>
						src="<?php echo $images->image_intro; ?>" alt="<?php echo htmlspecialchars($images->image_fulltext_alt); ?>"/>
						</div>
					<?php endif; ?>
					</span>
				<?php endif; ?>
				
				<?php if ($this->params->get('all_tags_show_tag_description', 0)) : ?> <!-- default =0 Crock -->
					<span class="tag-body">
						<?php echo JHtml::_('string.truncate', $item->description, $this->params->get('tag_list_item_maximum_characters')); ?>
					</span>
				<?php endif; ?>
				
				
			</li>

		<?php if (($i == 0 && $n == 1) || $i == $n - 1 || $bscolumns == 1 || (($i + 1) % $bscolumns == 0)) : ?>
			</ul>
		<?php endif; ?>

	<?php endforeach; ?>
<?php endif;?>

<?php // Add pagination links ?>
<?php if (!empty($this->items)) : ?>
	<?php if (($this->params->def('show_pagination', 2) == 1  || ($this->params->get('show_pagination') == 2)) && ($this->pagination->pagesTotal > 1)) : ?>
	<div class="pagination">

		<?php if ($this->params->def('show_pagination_results', 1)) : ?>
			<p class="counter pull-right">
				<?php echo $this->pagination->getPagesCounter(); ?>
			</p>
		<?php endif; ?>

		<?php echo $this->pagination->getPagesLinks(); ?>
	</div>
	<?php endif; ?>
</form>
<?php endif; ?>
