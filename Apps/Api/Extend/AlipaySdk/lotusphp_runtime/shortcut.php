<?php
//命名冲突，改为CC，原名称为C
function CC($className)
{
	return LtObjectUtil::singleton($className);
}
