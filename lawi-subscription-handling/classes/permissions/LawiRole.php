<?php

namespace wps\lawi\permissions;

class LawiRole
{

    public string $label = '';
    public string $slug = '';
    public string $textDomain = 'lawi-text';
    public array $permissons = [];

    /**
     * @param string $label
     * @param string $slug
     * @param array $permissions
     */
    public function __construct(string $label, string $slug, array $permissions=[])
    {
        $this->label = $label;
        $this->slug = $slug;
        $this->permissons = $permissions;
        $this->update();
    }

    /**
     * @return void
     */
    public function update(): void
    {
        $this->delete();
        $this->store();
    }

    /**
     * @return void
     */
    public function store(): void
    {
        if( null === get_role($this->slug) ){
            add_role($this->slug, __($this->label, $this->textDomain));
            $this->addPermissionsToRole();
        }
    }

    /**
     * @return void
     */
    public function delete(): void
    {
        remove_role($this->slug);
    }

    /**
     * @return void
     */
    private function addPermissionsToRole(): void
    {
        $role = get_role( $this->slug);
        if( null !== $role && count($this->permissons)>0){
            foreach ($this->permissons as $permission => $grant){
                $role->add_cap($permission, $grant);
            }
        }
    }

    /**
     * @param string $slug
     * @return string
     */
    public static function getRoleLabelBySlug(string $slug): string
    {
        global $wpdb;

        $sql = "SELECT option_value FROM {$wpdb->prefix}options WHERE option_name='wp_user_roles' LIMIT 1";
        $result = $wpdb->get_results($sql);
        if(isset($result[0]->option_value)){
            $data = maybe_unserialize($result[0]->option_value);
            if(isset($data[$slug])){
                return $data[$slug]['name'];
            }
        }
        return 'title not found';
    }

    /**
     * @param string $slug
     * @return static
     */
    public static function getInstanceByRole(string $slug)
    {
        $role = get_role($slug);
        return new static(self::getRoleLabelBySlug($slug), $role->name, $role->capabilities);
    }
}