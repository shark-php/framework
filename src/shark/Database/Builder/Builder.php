<?php


namespace Shark\Database\Builder;
//20030305
use NilPortugues\Sql\QueryBuilder\Builder\GenericBuilder;
use NilPortugues\Sql\QueryBuilder\Manipulation\AbstractBaseQuery;
use NilPortugues\Sql\QueryBuilder\Manipulation\Select;
use NilPortugues\Sql\QueryBuilder\Syntax\OrderBy;
use NilPortugues\Sql\QueryBuilder\Syntax\Where;
use React\MySQL\QueryResult;
use React\Promise\PromiseInterface;
use Shark\Database\Interfaces\DriverInterface;
use Shark\Database\Paginator\SimplePaginatorTrait;
use function React\Async\await;


class Builder
{
    use SimplePaginatorTrait;

    protected string $table = "";

    protected GenericBuilder $builder;
    protected AbstractBaseQuery|Where|null $query = null;

    private bool $select_called = false;

    public function __construct(?GenericBuilder $builder)
    {
        $this->builder = is_null($builder) ? new GenericBuilder() : $builder;
    }


    /**
     * Exec and get one row
     *
     * @param DriverInterface $driver
     * @return PromiseInterface<array|null>
     */
    public function rawQuery(DriverInterface $driver) : PromiseInterface
    {
        $sql    = $this->builder->write($this->query);
        $values = $this->builder->getValues();

        return $driver
            ->exec($sql,$values)
            ->then(function(QueryResult $result) {
                return $this->checkResultRow($result);
            });
    }

    /**
     * Exec and get rows
     *
     * @param DriverInterface $driver
     * @return PromiseInterface<array>
     */
    public function rawQueries(DriverInterface $driver) : PromiseInterface
    {
        $sql    = $this->builder->write($this->query);
        $values = $this->builder->getValues();

        return $driver
            ->exec($sql,$values)
            ->then(function(QueryResult $result){
                return $result->resultRows;
            });
    }

    /**
     * Exec a Query
     *
     * @param DriverInterface $driver
     * @return PromiseInterface<QueryResult>
     */
    public function execQuery(DriverInterface $driver) : PromiseInterface
    {
        $sql    = $this->builder->write($this->query);
        $values = $this->builder->getValues();

        return $driver
            ->exec($sql,$values)
            ->then(function(QueryResult $result){
                return $result;
            });
    }


    public function table(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    public function select(array $columns = ["*"]): static
    {
        $this->select_called    = true;
        $this->query            = $this->_select($columns);
        return $this;
    }

    public function where($operator = "AND"): static
    {
        if (!$this->query)
            $this->query = $this->builder->select($this->table);
        $this->query = $this->query->where($operator);
        return $this;
    }

    public function subWhere($operator = 'OR'): static
    {
        $this->query = $this->query->subWhere($operator);
        return $this;
    }

    public function equals($column, $value): static
    {
        $this->query->equals($column, $value);
        return $this;
    }

    public function like($column, $value): static
    {
        $this->query->like($column, $value);
        return $this;
    }

    public function notLike($column, $value): static
    {
        $this->query->notLike($column, $value);
        return $this;
    }

    public function greaterThan($column, $value): static
    {
        $this->query->greaterThan($column, $value);
        return $this;
    }

    public function greaterThanOrEqual($column, $value): static
    {
        $this->query->greaterThanOrEqual($column, $value);
        return $this;
    }

    public function lessThan($column, $value): static
    {
        $this->query->lessThan($column, $value);
        return $this;
    }

    public function lessThanOrEqual($column, $value): static
    {
        $this->query->lessThan($column, $value);
        return $this;
    }

    public function match(array $columns, array $values): static
    {
        $this->query->match($columns, $values);
        return $this;
    }

    public function matchBoolean(array $columns, array $values): static
    {
        $this->query->matchBoolean($columns, $values);
        return $this;
    }

    public function matchWithQueryExpansion(array $columns, array $values): static
    {
        $this->query->matchWithQueryExpansion($columns, $values);
        return $this;
    }

    public function in($column, array $values): static
    {
        $this->query->in($column, $values);
        return $this;
    }

    public function notIn($column, array $values): static
    {
        $this->query->notIn($column, $values);
        return $this;
    }

    public function between($column, $a, $b): static
    {
        $this->query->between($column, $a, $b);
        return $this;
    }

    public function notBetween($column, $a, $b): static
    {
        $this->query->notBetween($column, $a, $b);
        return $this;
    }

    public function isNull($column): static
    {
        $this->query->isNull($column);
        return $this;
    }

    public function isNotNull($column): static
    {
        $this->query->isNotNull($column);
        return $this;
    }

    public function exists(Select $select): static
    {
        $this->query->exists($select);
        return $this;
    }

    public function notExists(Select $select): static
    {
        $this->query->notExists($select);
        return $this;
    }

    public function addBitClause($column, $value): static
    {
        $this->query->addBitClause($column, $value);
        return $this;
    }

    public function asLiteral($literal): static
    {
        $this->query->asLiteral($literal);
        return $this;
    }

    public function end(): static
    {
        $this->query = $this->query->end();
        return $this;
    }

    public function limit($start, $count = 0): static
    {
        $this->query = $this->query->limit($start, $count);
        return $this;
    }

    public function orderBy($field,$order = OrderBy::ASC,$table = null): static
    {
        $this->query->orderBy($field, $order,$table);
        return $this;
    }

    public function leftJoin($table, $selfColumn = null, $refColumn = null, $columns = []): static
    {
        $this->query->leftJoin($table, $selfColumn, $refColumn, $columns);
        return $this;
    }

    public function join($table, $selfColumn = null, $refColumn = null, $columns = [], $joinType = null ): static
    {
        $this->query->join($table, $selfColumn, $refColumn, $columns,$joinType);
        return $this;
    }

    public function rightJoin($table, $selfColumn = null, $refColumn = null, $columns = []): static
    {
        $this->query->rightJoin($table, $selfColumn, $refColumn, $columns);
        return $this;
    }

    public function crossJoin($table, $selfColumn = null, $refColumn = null, $columns = []): static
    {
        $this->query->crossJoin($table, $selfColumn, $refColumn, $columns);
        return $this;
    }

    public function innerJoin($table, $selfColumn = null, $refColumn = null, $columns = []): static
    {
        $this->query->innerJoin($table, $selfColumn, $refColumn, $columns);
        return $this;
    }

    
    private function checkResultRow(QueryResult $result) : ?array {
        if (!@$result->resultRows[0])
            return null;
        return $result->resultRows[0];
    }

    public function simplePaginate(DriverInterface $driver, $per_page = 20, $page = 1, array $columns = ["*"]): PromiseInterface
    {
        if (!$this->query)
            $this->query = $this->_select($columns);
        list($start,$count) = $this->makePaginateCount($page,$per_page);

        $this->query
            ->limit($start,$count);

        $sql    = $this->builder->write($this->query);
        $values = $this->builder->getValues();

        return $driver
            ->exec($sql,$values)
            ->then(function(QueryResult $result) use ($per_page,$page,$count){
                return $this->simplePaginateResponse($result->resultRows,$per_page,$page,$count);
            });
    }

    public function paginate(DriverInterface $driver, int $per_page = 20, int $page = 1, array $columns = ["*"]) : PromiseInterface
    {
        if (!$this->query)
            $this->query = $this->_select($columns);
        list($start,$count) = $this->makePaginateCount($page,$per_page);

        $count_query    = clone($this->query)->count();

        $count_sql      = $this->builder->write($count_query);
        $count_values   = $this->builder->getValues();

        $this->query
            ->limit($start,$count);

        $sql    = $this->builder->write($this->query);
        $values = $this->builder->getValues();
        $count =  await($driver
            ->exec($count_sql,$count_values)
            ->then(function(QueryResult $result) use ($per_page,$page){
                return $result->resultRows[0]["COUNT(*)"];
            }));

        return $driver->exec($sql,$values)->then(function(QueryResult $result) use ($per_page,$page,$count){
            return $this->paginateResponse($result->resultRows,$per_page,$page,$count);
        });
    }

    private function _select(array $columns = ["*"]): AbstractBaseQuery|Select
    {
        return $this->builder->select($this->table,$columns);
    }
}