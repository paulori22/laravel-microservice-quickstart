import React, { useState } from "react";
import { IconButton, Menu as MuiMenu, MenuItem } from "@material-ui/core";
import MenuIcon from "@material-ui/icons/Menu";
import routes, { MyRouteProps } from "../../routes";
import { Link } from "react-router-dom";

const listRoutes = {
  dashboard: "Dashboard",
  "categories.list": "Categorias",
  "cast_members.list": "Membros do elenco",
  "genres.list": "Gêneros",
  "videos.list": "Vídeos",
  uploads: "Uploads",
};
const menuRoutes = routes.filter((route) =>
  Object.keys(listRoutes).includes(route.name)
);

export const Menu: React.FC = () => {
  const [anchorEl, setAnchorEl] = useState(null);

  const handleOpen = (event: any) => setAnchorEl(event.currentTarget);
  const handleClose = () => setAnchorEl(null);
  const open = Boolean(anchorEl);

  return (
    <>
      <IconButton
        edge="start"
        color="inherit"
        aria-label="open drawer"
        aria-controls="menu-appbar"
        aria-haspopup="true"
        onClick={handleOpen}
      >
        <MenuIcon />
      </IconButton>
      <MuiMenu
        id="menu-appbar"
        open={open}
        anchorEl={anchorEl}
        onClose={handleClose}
        anchorOrigin={{ vertical: "bottom", horizontal: "center" }}
        transformOrigin={{ vertical: "top", horizontal: "center" }}
        getContentAnchorEl={null}
      >
        {Object.keys(listRoutes).map((routeName, key) => {
          const route = menuRoutes.find(
            (route) => route.name === routeName
          ) as MyRouteProps;
          return (
            <MenuItem
              key={key}
              component={Link}
              to={route.path as string}
              onClick={handleClose}
            >
              {listRoutes[routeName]}
            </MenuItem>
          );
        })}
      </MuiMenu>
    </>
  );
};
