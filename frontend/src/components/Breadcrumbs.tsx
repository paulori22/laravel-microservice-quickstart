import React from "react";
import {
  Container,
  Link,
  Typography,
  Breadcrumbs as MuiBreadcrumbs,
  Box,
} from "@material-ui/core";
import { makeStyles, Theme } from "@material-ui/core/styles";
import { Route } from "react-router";
import { Link as RouterLink } from "react-router-dom";
import { Location } from "history";
import routes from "../routes";
import RouteParser from "route-parser";

const breadcrumbNameMap = {};
routes.forEach(
  (route) => (breadcrumbNameMap[route.path as string] = route.label)
);

const useStyles = makeStyles((theme: Theme) => ({
  linkRouter: {
    color: theme.palette.secondary.main,
    "&:focus, &:active": {
      color: theme.palette.secondary.main,
    },
    "&:hover": {
      color: theme.palette.secondary.dark,
    },
  },
}));

const LinkRouter = (props) => <Link {...props} component={RouterLink} />;

export default function Breadcrumbs() {
  const classes = useStyles();

  function makeBreadcrumb(location: Location) {
    const pathnames = location.pathname.split("/").filter((x) => x);
    pathnames.unshift("/");
    return (
      <MuiBreadcrumbs aria-label="breadcrumb">
        {pathnames.map((value, index) => {
          const last = index === pathnames.length - 1;
          const to = `${pathnames
            .slice(0, index + 1)
            .join("/")
            .replace("//", "/")}`;

          const route = Object.keys(breadcrumbNameMap).find((path) =>
            new RouteParser(path).match(to)
          );

          if (route === undefined) {
            return false;
          }

          return last ? (
            <Typography color="textPrimary" key={to}>
              {breadcrumbNameMap[route]}
            </Typography>
          ) : (
            <LinkRouter
              className={classes.linkRouter}
              color="inherit"
              to={to}
              key={to}
            >
              {breadcrumbNameMap[route]}
            </LinkRouter>
          );
        })}
      </MuiBreadcrumbs>
    );
  }

  return (
    <Container>
      <Box paddingTop={2} paddingBottom={1}>
        <Route>
          {({ location }: { location: Location }) => makeBreadcrumb(location)}
        </Route>
      </Box>
    </Container>
  );
}
