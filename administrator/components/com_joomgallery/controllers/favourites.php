<?php
// $HeadURL: https://joomgallery.org/svn/joomgallery/JG-2.0/JG/trunk/administrator/components/com_joomgallery/controllers/votes.php $
// $Id: votes.php 3378 2011-10-07 18:37:56Z aha $
/****************************************************************************************\
**   JoomGallery 2                                                                      **
**   By: JoomGallery::ProjectTeam                                                       **
**   Copyright (C) 2008 - 2011  JoomGallery::ProjectTeam                                **
**   Based on: JoomGallery 1.0.0 by JoomGallery::ProjectTeam                            **
**   Released under GNU GPL Public License                                              **
**   License: http://www.gnu.org/copyleft/gpl.html or have a look                       **
**   at administrator/components/com_joomgallery/LICENSE.TXT                            **
\****************************************************************************************/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

/**
 * JoomGallery Favourites Controller
 *
 * @package JoomGallery
 * @since   2.0
 */
class JoomGalleryControllerFavourites extends JoomGalleryController
{
  /**
   * Resets all favourites in the gallery and deletes all created zips of favourites
   *
   * @return  void
   * @since   2.0
   */
  public function reset()
  {
    $model = $this->getModel('favourites');
    if(!$model->reset())
    {
      $this->setRedirect($this->_ambit->getRedirectUrl('maintenance&tab=favourites'), $model->getError(), 'error');
    }
    else
    {
      $this->setRedirect($this->_ambit->getRedirectUrl('maintenance&tab=favourites'), JText::_('COM_JOOMGALLERY_MAIMAN_FV_MSG_ALL_FAVOURITES_DELETED'));
    }
  }

  /**
   * Synchronizes the entries in the users table of JoomGallery
   * with users registered and deletes all zips of favourites.
   *
   * @return  void
   * @since   2.0
   */
  public function synchronize()
  {
    $model = $this->getModel('favourites');
    if(!$model->synchronize())
    {
      $this->setRedirect($this->_ambit->getRedirectUrl('maintenance&tab=favourites'), $model->getError(), 'error');
    }
    else
    {
      $this->setRedirect($this->_ambit->getRedirectUrl('maintenance&tab=favourites'), JText::_('COM_JOOMGALLERY_MAIMAN_FV_MSG_FAVOURITES_SYNCHRONIZED'));
    }
  }
}